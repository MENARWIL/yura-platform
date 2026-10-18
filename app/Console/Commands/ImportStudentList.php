<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Importa una lista de estudiantes desde un CSV que vive FUERA del repositorio (son datos de menores).
 *
 * Formato (separador «;», con encabezado): curso;paralelo;paterno;materno;nombres
 * Quien tiene un solo apellido va con PATERNO vacío y el apellido en MATERNO.
 *
 *   php artisan students:import ruta/lista.csv                       (simulación: no cambia nada)
 *   php artisan students:import ruta/lista.csv --apply               (aplica)
 *   php artisan students:import ruta/lista.csv --apply --replace-test (además borra los estudiantes de prueba)
 */
class ImportStudentList extends Command
{
    protected $signature = 'students:import
        {file : CSV con curso;paralelo;paterno;materno;nombres}
        {--apply : Aplica los cambios (sin esto solo se simula)}
        {--replace-test : Borra antes los estudiantes de prueba (nombres tipo «Juan Quispe - 1»)}
        {--capacity=40 : Capacidad mínima de cada paralelo importado}';

    protected $description = 'Importa estudiantes reales desde un CSV y, si se pide, reemplaza los de prueba';

    /** Los estudiantes de prueba del seeder se llaman «Nombre Apellido - N». */
    private const TEST_NAME = '/ - \d+$/u';

    public function handle(): int
    {
        $rows = $this->readRows((string) $this->argument('file'));
        if ($rows === null) {
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'MODO APLICAR' : 'SIMULACIÓN (no se cambia nada; use --apply para aplicar)');

        $toDelete = collect();
        if ($this->option('replace-test')) {
            $toDelete = Student::withTrashed()->get()->filter(fn (Student $s) => preg_match(self::TEST_NAME, (string) $s->name));
            $real = Student::withTrashed()->count() - $toDelete->count();
            $this->line("Estudiantes de prueba a borrar: {$toDelete->count()} (los demás, {$real}, no se tocan)");
        }

        $perGroup = collect($rows)->groupBy(fn ($r) => $r['curso'] . ' / ' . $r['paralelo'])->map->count();
        $perGroup->each(fn ($n, $g) => $this->line("  {$g}: {$n} estudiantes"));

        if (! $apply) {
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        DB::transaction(function () use ($rows, $toDelete, &$created, &$skipped) {
            foreach ($toDelete as $student) {
                Grade::where('student_id', $student->id)->delete();
                Attendance::where('student_id', $student->id)->delete();
                $student->familiares()->detach();
                $student->forceDelete();
            }

            $capacity = max(1, (int) $this->option('capacity'));
            $parallels = [];
            foreach ($rows as $row) {
                $key = $row['curso'] . '|' . $row['paralelo'];
                if (! isset($parallels[$key])) {
                    $course = Course::firstOrCreate(['name' => $row['curso']]);
                    $parallel = Parallel::firstOrCreate(
                        ['course_id' => $course->id, 'name' => $row['paralelo']],
                        ['max_students' => $capacity]
                    );
                    if ($parallel->max_students < $capacity) {
                        $parallel->update(['max_students' => $capacity]);
                    }
                    $parallels[$key] = [$course, $parallel];
                }
                [$course, $parallel] = $parallels[$key];

                $name = trim(preg_replace('/\s+/', ' ', $row['paterno'] . ' ' . $row['materno'] . ' ' . $row['nombres']));
                if (Student::where('parallel_id', $parallel->id)->where('name', $name)->exists()) {
                    $skipped++; // ya estaba: se puede volver a correr sin duplicar

                    continue;
                }

                Student::create([
                    'name' => $name,
                    'last_name' => $row['paterno'] !== '' ? $row['paterno'] : null,
                    'second_last_name' => $row['materno'] !== '' ? $row['materno'] : null,
                    'first_name' => $row['nombres'],
                    'course' => $course->name,
                    'course_id' => $course->id,
                    'parallel_id' => $parallel->id,
                    'status' => 'activo',
                    'attendance' => 0,
                ]);
                $created++;
            }

            // La auditoría no guarda nombres: son datos de menores.
            AuditLog::create([
                'user_id' => null,
                'action' => 'students.import',
                'entity' => 'student',
                'entity_id' => null,
                'ip_address' => null,
                'details' => ['created' => $created, 'skipped' => $skipped, 'test_deleted' => $toDelete->count()],
                'status' => 'success',
                'created_at' => now(),
            ]);
        });

        $this->info("Listo: {$created} creados, {$skipped} ya existían, {$toDelete->count()} de prueba borrados.");

        return self::SUCCESS;
    }

    /** @return list<array{curso:string,paralelo:string,paterno:string,materno:string,nombres:string}>|null */
    private function readRows(string $path): ?array
    {
        if (! is_file($path)) {
            $this->error('No existe el archivo.');

            return null;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle, 0, ';');
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header ?: []);
        $needed = ['curso', 'paralelo', 'paterno', 'materno', 'nombres'];
        if (array_diff($needed, $header)) {
            $this->error('El encabezado debe ser: ' . implode(';', $needed));
            fclose($handle);

            return null;
        }

        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($handle, 0, ';')) !== false) {
            $line++;
            if ($cells === [null] || count($cells) < count($header)) {
                continue;
            }
            $row = array_map(fn ($v) => trim(preg_replace('/\s+/', ' ', (string) $v)), array_combine($header, array_slice($cells, 0, count($header))));
            if ($row['nombres'] === '' || ($row['paterno'] === '' && $row['materno'] === '') || $row['curso'] === '' || $row['paralelo'] === '') {
                $this->error("Línea {$line}: faltan datos (curso, paralelo, al menos un apellido y nombres).");
                fclose($handle);

                return null;
            }
            $rows[] = array_intersect_key($row, array_flip($needed));
        }
        fclose($handle);

        return $rows;
    }
}
