<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private ?int $courseId = null,
        private ?int $parallelId = null,
    ) {
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Student::with(['padre', 'profesor', 'parallel', 'course'])
            ->when($this->courseId, fn ($query) => $query->where('course_id', $this->courseId))
            ->when($this->parallelId, fn ($query) => $query->where('parallel_id', $this->parallelId))
            ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Edad',
            'Género',
            'Nivel',
            'Curso',
            'Paralelo',
            'Padre / Responsable',
            'Profesor Tutor',
            'Estado',
            'Puntaje Robot',
            'Nota Escritura',
            'Nota Examen',
            'Promedio Final',
            'Asistencia %',
            'Fecha Registro'
        ];
    }

    public function map($student): array
    {
        // Academic figures are computed live from the `grades` and
        // `attendance` tables (see App\Models\Student::real*() helpers)
        // instead of reading the legacy static columns on `students`,
        // which are not kept reliably in sync with real grades/attendance.
        $realRobot = $student->realRobotScore();
        $realWriting = $student->realWritingScore();
        $realExam = $student->realExamScore();
        $realAverage = $student->realOverallAverage();
        $realAttendance = $student->realAttendancePercent();

        return [
            $student->id,
            $student->name ?? $student->nombre,
            $student->age ?? $student->edad,
            $student->gender ?? $student->genero,
            mb_strtoupper($student->level ?? $student->nivel, 'UTF-8'),
            optional($student->course)->name ?? $student->getAttribute('course') ?? 'N/A',
            optional($student->parallel)->name ?? 'N/A',
            optional($student->padre)->name ?? 'N/A',
            optional($student->profesor)->name ?? 'N/A',
            mb_strtoupper($student->status ?? $student->estado, 'UTF-8'),
            $realRobot ?? 'N/A',
            $realWriting ?? 'N/A',
            $realExam ?? 'N/A',
            ($realAverage !== null ? $realAverage . '%' : 'N/A'),
            ($realAttendance !== null ? $realAttendance . '%' : 'N/A'),
            $student->registration_date ?? $student->fecha_registro,
        ];
    }
}
