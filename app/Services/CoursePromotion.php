<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
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
