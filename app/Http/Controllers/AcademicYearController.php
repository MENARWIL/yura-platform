<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use App\Services\UserAdminException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Pantalla «Gestiones» del administrador (director y administrador). La ruta ya exige el rol admin. */
class AcademicYearController extends Controller
{
    public function __construct(private readonly AcademicYearService $years)
    {
    }

    public function index()
    {
        $counts = fn (string $table) => DB::table($table)->selectRaw('academic_year_id, count(*) as n')->groupBy('academic_year_id')->pluck('n', 'academic_year_id');
        $enrollments = $counts('enrollments');
        $grades = $counts('grades');
        $attendance = $counts('attendance');

        $years = AcademicYear::with('closedBy')->orderByDesc('year')->get()->each(function (AcademicYear $y) use ($enrollments, $grades, $attendance) {
            $y->counts = ['enrollments' => $enrollments[$y->id] ?? 0, 'grades' => $grades[$y->id] ?? 0, 'attendance' => $attendance[$y->id] ?? 0];
        });

        return view('admin.years.index', compact('years'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['year' => ['required', 'integer', 'between:2000,2100']]);

        return $this->perform(fn () => $this->years->create($request->user(), (int) $data['year']), "Gestión {$data['year']} creada.");
    }

    public function current(Request $request, AcademicYear $year): RedirectResponse
    {
        return $this->perform(fn () => $this->years->setCurrent($request->user(), $year), "La gestión actual ahora es {$year->name}.");
    }

    public function close(Request $request, AcademicYear $year): RedirectResponse
    {
        $data = $request->validate(['next_year_id' => ['nullable', 'integer', 'exists:academic_years,id']]);
        $next = isset($data['next_year_id']) ? AcademicYear::find($data['next_year_id']) : null;

        return $this->perform(fn () => $this->years->close($request->user(), $year, $next), "{$year->name} cerrada.");
    }

    public function reopen(Request $request, AcademicYear $year): RedirectResponse
    {
        return $this->perform(fn () => $this->years->reopen($request->user(), $year), "{$year->name} reabierta.");
    }

    private function perform(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (UserAdminException|\InvalidArgumentException $e) {
            return redirect()->route('admin.years.index')->with('error', $e->getMessage());
        }

        return redirect()->route('admin.years.index')->with('success', $success);
    }
}
