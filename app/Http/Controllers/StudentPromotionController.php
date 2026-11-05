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

/** Pasar un curso completo al siguiente al empezar una gestión (ver CoursePromotion). */
class StudentPromotionController extends Controller
{
    public function create(Request $request): View
    {
        abort_unless(Auth::user()?->canManageYears(), 403);

        $parallels = Parallel::with('course')->withCount(['students' => fn ($q) => $q->active()])
            ->orderBy('course_id')->orderBy('name')->get();
        $from = $parallels->firstWhere('id', $request->integer('from'));
        $students = $from
            ? Student::active()->where('parallel_id', $from->id)->orderedForList()->get()
            : collect();

        return view('students.promote', [
            'parallels' => $parallels,
            'from' => $from,
            'students' => $students,
            'year' => AcademicYear::current(),
        ]);
    }

    public function store(Request $request, CoursePromotion $promotion): RedirectResponse
    {
        abort_unless(Auth::user()?->canManageYears(), 403);

        $data = $request->validate([
            'from_parallel_id' => ['required', 'integer', 'exists:parallels,id'],
            'to_parallel_id' => ['required', 'integer', 'exists:parallels,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'distinct'],
            'add_year' => ['nullable', 'boolean'],
        ], [
            'to_parallel_id.required' => 'Elige el paralelo al que pasan los estudiantes.',
            'student_ids.required' => 'Marca al menos un estudiante para promover.',
        ]);

        $from = Parallel::findOrFail($data['from_parallel_id']);
        $to = Parallel::with('course')->findOrFail($data['to_parallel_id']);

        try {
            $count = $promotion->promote(Auth::user(), $from, $to, $data['student_ids'], $request->boolean('add_year'));
        } catch (UserAdminException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('students.index', ['parallel_id' => $to->id])
            ->with('success', "Se promovieron {$count} estudiante(s) a {$to->course?->name} {$to->name}. Revisa y completa sus datos si hace falta.");
    }
}
