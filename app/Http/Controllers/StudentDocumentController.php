<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Student;
use App\Services\TrimesterReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Documentos de secretaría para cada estudiante (administrador, director y Panel Administrativo):
 * constancia de estudiante regular y boletín de notas trimestrales, en PDF.
 */
class StudentDocumentController extends Controller
{
    /** Constancia de estudiante regular de la gestión actual. Un estudiante dado de baja no es regular. */
    public function constancia(Student $student)
    {
        if ($student->isWithdrawn()) {
            return back()->with('error', 'El estudiante está dado de baja: no se puede emitir una constancia de estudiante regular.');
        }

        $student->loadMissing(['parallel.course', 'course']);

        return Pdf::loadView('reports.constancia_pdf', $this->common($student))
            ->setPaper('letter', 'portrait')
            ->download('constancia_' . $this->slug($student) . '.pdf');
    }

    /** Boletín con la nota final de cada trimestre por asignatura y el promedio anual. */
    public function boletin(Student $student)
    {
        $student->loadMissing(['parallel.course', 'course']);
        $rows = TrimesterReport::forStudent($student);

        return Pdf::loadView('reports.boletin_pdf', $this->common($student) + ['rows' => $rows])
            ->setPaper('letter', 'portrait')
            ->download('boletin_' . $this->slug($student) . '.pdf');
    }

    private function common(Student $student): array
    {
        $city = config('yura.org_city');
        $date = now()->locale('es')->translatedFormat('j \d\e F \d\e Y');

        return [
            'student' => $student,
            'year' => AcademicYear::current(),
            'orgName' => config('yura.org_name') ?: 'Unidad Educativa',
            'placeAndDate' => $city ? "{$city}, {$date}" : $date,
            'issuedBy' => Auth::user(),
        ];
    }

    private function slug(Student $student): string
    {
        return Str::slug($student->name, '_') ?: (string) $student->id;
    }
}
