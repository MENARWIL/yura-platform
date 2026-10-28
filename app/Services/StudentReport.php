<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Student;

/**
 * Resumen académico de un estudiante. Lo usan el panel web de la familia y la API de la app,
 * para que los dos muestren exactamente los mismos números.
 *
 * Requiere cargadas las relaciones `grades.subject` y `attendances`.
 */
class StudentReport
{
    /** Datos de identidad que ve la app: nunca contacto de familiares ni del profesor. */
    public static function identity(Student $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->name,
            'course' => $student->course_name,
            'parallel' => $student->parallel?->name,
            'teacher' => $student->profesor?->name,
        ];
    }

    /** Fila de lista: identidad y tres números. Requiere `grades.subject` y `attendances` cargados. */
    public static function listItem(Student $student): array
    {
        $report = self::for($student);

        return self::identity($student) + [
            'average' => $report['average'],
            'attendance_percent' => $report['attendance_percent'],
            'robot_completed' => $report['robot_completed'],
        ];
    }

    /** El informe como lo recibe la app: los trimestres van como lista ordenada. */
    public static function forApp(Student $student): array
    {
        $report = self::for($student);
        $report['quarter_averages'] = collect($report['quarter_averages'])
            ->map(fn ($average, $quarter) => ['quarter' => (int) $quarter, 'average' => $average])
            ->values();

        return $report;
    }

    public static function for(Student $student): array
    {
        $academicGrades = $student->grades
            ->where('status', 'completed')
            ->where('is_robot_activity', false)
            ->filter(fn ($grade) => $grade->score !== null);
        $activityGrades = $academicGrades->where('type', 'activity');
        $examGrades = $academicGrades->where('type', 'exam');
        $robotGrades = $student->grades
            ->where('status', 'completed')
            ->where('is_robot_activity', true)
            ->filter(fn ($grade) => $grade->score !== null);
        // La licencia (ausencia justificada) no entra en el total; ver Attendance::presentPercent().
        $countedAttendance = $student->attendances->reject(
            fn ($attendance) => Attendance::normalizeStatus($attendance->status) === 'licencia'
        );
        $attendanceTotal = $countedAttendance->count();
        $attendancePresent = $countedAttendance->filter(
            fn ($attendance) => Attendance::normalizeStatus($attendance->status) === 'presente'
        )->count();

        return [
            'average' => $academicGrades->count() ? round($academicGrades->avg('score'), 2) : null,
            'activity_average' => $activityGrades->count() ? round($activityGrades->avg('score'), 2) : null,
            'exam_average' => $examGrades->count() ? round($examGrades->avg('score'), 2) : null,
            'robot_average' => $robotGrades->count() ? round($robotGrades->avg('score'), 2) : null,
            'activity_count' => $activityGrades->count(),
            'exam_count' => $examGrades->count(),
            'robot_count' => $robotGrades->count(),
            'quarter_averages' => collect([1, 2, 3])->mapWithKeys(function ($quarter) use ($academicGrades) {
                $grades = $academicGrades->where('quarter', $quarter);

                return [$quarter => $grades->count() ? round($grades->avg('score'), 2) : null];
            }),
            'subjects' => $academicGrades
                ->groupBy(fn ($grade) => $grade->subject?->name ?? 'Sin materia')
                ->map(fn ($grades, $name) => [
                    'name' => $name,
                    'average' => round($grades->avg('score'), 2),
                    'count' => $grades->count(),
                ])->values(),
            'attendance_percent' => $attendanceTotal ? round(($attendancePresent / $attendanceTotal) * 100, 2) : null,
            'attendance_present' => $attendancePresent,
            'attendance_total' => $attendanceTotal,
            'robot_total' => $student->grades->where('is_robot_activity', true)->count(),
            'robot_completed' => $student->grades
                ->where('is_robot_activity', true)
                ->where('status', 'completed')
                ->count(),
        ];
    }
}
