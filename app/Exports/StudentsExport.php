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
        return Student::with(['padre', 'profesor'])->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Edad',
            'Género',
            'Nivel',
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
            $student->nombre,
            $student->edad,
            $student->genero,
            strtoupper($student->nivel),
            $student->padre->name ?? 'N/A',
            $student->profesor->name ?? 'N/A',
            strtoupper($student->estado),
            $student->puntaje,
            $student->nota_escritura,
            $student->nota_examen,
            $student->promedio . '%',
            $student->asistencia . '%',
            $student->fecha_registro,
        ];
    }
}
