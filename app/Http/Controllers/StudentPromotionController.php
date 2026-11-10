<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Parallel;
use App\Models\Student;
use App\Services\CoursePromotion;
use App\Services\UserAdminException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Pasar un curso completo al siguiente al empezar una gestión (ver CoursePromotion).
 * Los estudiantes vienen marcados según su nota final de la gestión anterior (51 o más: aprueba);
 * los del último curso se registran como egresados eligiendo «Egresan» como destino.
 */
class StudentPromotionController extends Controller
{
    /** Valor del destino que significa «terminaron el colegio». */
    public const GRADUATE = 'egreso';

    public function create(Request $request, CoursePromotion $promotion): View
    {
        abort_unless(Auth::user()?->canManageYears(), 403);

        $parallels = Parallel::with('course')->withCount(['students' => fn ($q) => $q->active()])
            ->orderBy('course_id')->orderBy('name')->get();
        $from = $parallels->firstWhere('id', $request->integer('from'));
        $students = $from
            ? Student::active()->where('parallel_id', $from->id)->orderedForList()->get()
            : collect();
        $previous = $promotion->previousYear();

        return view('students.promote', [
            'parallels' => $parallels,
            'from' => $from,
            'students' => $students,
            'year' => AcademicYear::current(),
            'previousYear' => $previous,
            'averages' => $previous ? $promotion->finalAverages($previous, $students->pluck('id')->all()) : [],
            'passing' => CoursePromotion::PASSING_SCORE,
        ]);
    }

    public function store(Request $request, CoursePromotion $promotion): RedirectResponse
    {
        abort_unless(Auth::user()?->canManageYears(), 403);

        $graduate = $request->input('to_parallel_id') === self::GRADUATE;
        $data = $request->validate([
            'from_parallel_id' => ['required', 'integer', 'exists:parallels,id'],
            'to_parallel_id' => $graduate ? ['required'] : ['required', 'integer', 'exists:parallels,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct'],
            'add_year' => ['nullable', 'boolean'],
        ], [
            'to_parallel_id.required' => 'Elige el paralelo al que pasan los estudiantes.',
            'student_ids.required' => 'Marca al menos un estudiante para promover.',
        ]);

        $from = Parallel::findOrFail($data['from_parallel_id']);

        try {
            if ($graduate) {
                $count = $promotion->graduate(Auth::user(), $from, $data['student_ids']);

                return redirect()
                    ->route('students.promote')
                    ->with('success', "{$count} estudiante(s) quedaron como egresados. Su información se conserva en «Dados de baja».");
            }

            $to = Parallel::with('course')->findOrFail($data['to_parallel_id']);
            $count = $promotion->promote(Auth::user(), $from, $to, $data['student_ids'], $request->boolean('add_year'));
        } catch (UserAdminException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('students.index', ['parallel_id' => $to->id])
            ->with('success', "Se promovieron {$count} estudiante(s) a {$to->course?->name} {$to->name}. Revisa y completa sus datos si hace falta.");
    }
}
