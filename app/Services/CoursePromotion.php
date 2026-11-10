<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Promoción de un curso completo al siguiente (por ejemplo, de 3.° A a 4.° A) al empezar una gestión:
 * en lugar de editar estudiante por estudiante, se pasan juntos los que aprobaron y después se
 * corrigen los datos que haga falta. Los que repiten se desmarcan y quedan en su paralelo.
 *
 * La matrícula de la gestión actual se registra sola al cambiar el curso (ver AppServiceProvider),
 * así que la gestión anterior conserva el curso viejo y la actual guarda el nuevo.
 */
class CoursePromotion
{
    /** Nota mínima de aprobación (escala de 1 a 100). */
    public const PASSING_SCORE = 51;

    /** La gestión anterior a la actual: de ella sale la nota final que decide quién pasa. */
    public function previousYear(): ?AcademicYear
    {
        $current = AcademicYear::current();

        return $current
            ? AcademicYear::where('year', '<', $current->year)->orderByDesc('year')->first()
            : null;
    }

    /**
     * Nota final de cada estudiante en una gestión: promedio de sus notas académicas completadas
     * (sin los retos del robot), igual que el «Promedio general» de su ficha.
     *
     * @param  array<int>  $studentIds
     * @return array<int, float> id del estudiante => promedio (solo los que tienen notas)
     */
    public function finalAverages(AcademicYear $year, array $studentIds): array
    {
        if (! $studentIds) {
            return [];
        }

        return Grade::withoutGlobalScope('academic_year')
            ->where('academic_year_id', $year->id)
            ->whereIn('student_id', $studentIds)
            ->where('status', 'completed')
            ->where('is_robot_activity', false)
            ->whereNotNull('score')
            ->groupBy('student_id')
            ->selectRaw('student_id, AVG(score) as average')
            ->pluck('average', 'student_id')
            ->map(fn ($avg) => round((float) $avg, 2))
            ->all();
    }

    /**
     * Egreso: los estudiantes marcados terminaron el colegio. No se borran: quedan dados de baja como
     * «Egresado», con el respaldo de sus notas de la gestión que terminaron.
     *
     * @param  array<int>  $studentIds
     * @return int cantidad de egresados
     */
    public function graduate(?User $actor, Parallel $from, array $studentIds): int
    {
        if (! $actor || ! $actor->canManageYears()) {
            throw new UserAdminException('Solo la dirección o el administrador pueden registrar egresos.');
        }

        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if (! $studentIds) {
            throw new UserAdminException('Marca al menos un estudiante que egresa.');
        }

        $year = AcademicYear::current();
        if (! $year || $year->isClosed()) {
            throw new UserAdminException('No hay una gestión actual abierta donde registrar el egreso.');
        }
        $finished = $this->previousYear();

        return DB::transaction(function () use ($from, $studentIds, $finished) {
            $students = Student::active()
                ->where('parallel_id', $from->id)
                ->whereIn('id', $studentIds)
                ->lockForUpdate()
                ->get();

            if ($students->count() !== count($studentIds)) {
                throw new UserAdminException('Algunos estudiantes marcados ya no están activos en el paralelo de origen. Recarga la página.');
            }

            foreach ($students as $student) {
                StudentWithdrawal::create([
                    'student_id' => $student->id,
                    'type' => 'egreso',
                    'reason' => StudentWithdrawal::TYPES['egreso'] . ($finished ? " ({$finished->name})" : ''),
                    'withdrawn_on' => now()->toDateString(),
                    'user_id' => Auth::id(),
                    'snapshot' => StudentWithdrawal::snapshotOf($student, $finished?->id),
                ]);
                $student->update(['status' => Student::STATUS_WITHDRAWN]);
            }

            AuditLog::log('students.graduate', 'student', null, [
                'from' => $from->loadMissing('course')->course?->name . ' ' . $from->name,
                'count' => $students->count(),
                'year' => $finished?->year,
            ]);

            return $students->count();
        });
    }

    /**
     * @param  array<int>  $studentIds  estudiantes marcados del paralelo de origen
     * @return int cantidad de estudiantes promovidos
     */
    public function promote(?User $actor, Parallel $from, Parallel $to, array $studentIds, bool $addYearToAge): int
    {
        if (! $actor || ! $actor->canManageYears()) {
            throw new UserAdminException('Solo la dirección o el administrador pueden promover cursos.');
        }
        if ($from->id === $to->id) {
            throw new UserAdminException('El paralelo de destino debe ser distinto del de origen.');
        }

        $studentIds = array_values(array_unique(array_map('intval', $studentIds)));
        if (! $studentIds) {
            throw new UserAdminException('Marca al menos un estudiante para promover.');
        }

        $year = AcademicYear::current();
        if (! $year || $year->isClosed()) {
            throw new UserAdminException('No hay una gestión actual abierta donde registrar la promoción.');
        }

        return DB::transaction(function () use ($actor, $from, $to, $studentIds, $addYearToAge, $year) {
            $to = Parallel::with('course')->lockForUpdate()->findOrFail($to->id);
            $students = Student::active()
                ->where('parallel_id', $from->id)
                ->whereIn('id', $studentIds)
                ->lockForUpdate()
                ->get();

            if ($students->count() !== count($studentIds)) {
                throw new UserAdminException('Algunos estudiantes marcados ya no están activos en el paralelo de origen. Recarga la página.');
            }

            // Si ya tienen notas o asistencia en esta gestión, todavía es la gestión del curso anterior:
            // cambiarles el curso ahora mezclaría su historial.
            if (Grade::whereIn('student_id', $studentIds)->exists() || Attendance::whereIn('student_id', $studentIds)->exists()) {
                throw new UserAdminException(
                    "Estos estudiantes ya tienen notas o asistencia en la {$year->name}. "
                    . 'La promoción se hace al empezar la nueva gestión: créala y márcala como actual en «Gestiones», y vuelve aquí.'
                );
            }

            $free = $to->max_students - $to->studentCount();
            if ($students->count() > $free) {
                throw new UserAdminException(
                    "El paralelo de destino tiene {$free} cupo(s) libre(s) y marcaste {$students->count()} estudiante(s). "
                    . 'Aumenta el cupo del paralelo o elige otro.'
                );
            }

            foreach ($students as $student) {
                $student->course_id = $to->course_id;
                $student->course = $to->course?->name;
                $student->parallel_id = $to->id;
                // El profesor se asigna por paralelo (asignación docente): el del curso anterior ya no corresponde.
                $student->teacher_user_id = null;
                if ($addYearToAge && $student->age !== null) {
                    $student->age = (int) $student->age + 1;
                }
                $student->save();
            }

            AuditLog::log('students.promote', 'student', null, [
                'from' => $from->loadMissing('course')->course?->name . ' ' . $from->name,
                'to' => $to->course?->name . ' ' . $to->name,
                'count' => $students->count(),
                'age_plus_one' => $addYearToAge,
                'year' => $year->year,
            ]);

            return $students->count();
        });
    }
}
