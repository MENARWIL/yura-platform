<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StudentsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Student::with(['padre', 'profesor', 'parallel', 'course'])->get();
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
        return [
            $student->id,
            $student->name ?? $student->nombre,
            $student->age ?? $student->edad,
            $student->gender ?? $student->genero,
            strtoupper($student->level ?? $student->nivel),
            optional($student->course)->name ?? $student->getAttribute('course') ?? 'N/A',
            optional($student->parallel)->name ?? 'N/A',
            optional($student->padre)->name ?? 'N/A',
            optional($student->profesor)->name ?? 'N/A',
            strtoupper($student->status ?? $student->estado),
            $student->score ?? $student->puntaje,
            $student->writing_score ?? $student->nota_escritura,
            $student->exam_score ?? $student->nota_examen,
            ($student->promedio ?? 0) . '%',
            ($student->attendance ?? $student->asistencia) . '%',
            $student->registration_date ?? $student->fecha_registro,
        ];
    }
}
