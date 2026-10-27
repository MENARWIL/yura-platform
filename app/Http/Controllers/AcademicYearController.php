<?php

namespace App\Http\Controllers;

use App\Exports\AcademicYearExport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\RobotActivity;
use App\Services\AcademicYearService;
use App\Services\UserAdminException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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

    /**
     * Consulta de una gestión (también de una cerrada): resumen, estudiantes por curso y paralelo y
     * el respaldo descargable. Solo lee; nunca modifica datos, así que no hace falta reabrirla.
     */
    public function show(Request $request, AcademicYear $year)
    {
        $year->load('closedBy');

        $filters = $request->validate([
            'course_id' => ['nullable', 'integer'],
            'parallel_id' => ['nullable', 'integer'],
        ]);
        $courseId = $filters['course_id'] ?? null;
        $parallelId = $filters['parallel_id'] ?? null;

        $countBy = fn (string $model) => $model::withoutGlobalScope('academic_year')
            ->where('academic_year_id', $year->id)
            ->selectRaw('student_id, count(*) as n')->groupBy('student_id')->pluck('n', 'student_id');
        $gradesByStudent = $countBy(Grade::class);
        $attendanceByStudent = $countBy(Attendance::class);
        $activities = RobotActivity::withoutGlobalScope('academic_year')->where('academic_year_id', $year->id)->count();

        $allEnrollments = Enrollment::where('academic_year_id', $year->id)->with(['course', 'parallel'])->get();

        // Los filtros ofrecen solo los cursos y paralelos que tuvo esa gestión; si hay un curso elegido,
        // el paralelo se limita a los de ese curso.
        $courses = $allEnrollments->pluck('course')->filter()->unique('id')->sortBy('name')->values();
        $parallels = $allEnrollments->pluck('parallel')->filter()->unique('id')
            ->when($courseId, fn ($items) => $items->where('course_id', (int) $courseId))
            ->sortBy(fn ($p) => [$p->course?->name ?? '~', $p->name])->values();

        $groups = Enrollment::where('academic_year_id', $year->id)
            ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
            ->when($parallelId, fn ($q) => $q->where('parallel_id', $parallelId))
            ->with(['course', 'parallel', 'student' => fn ($q) => $q->withTrashed()])
            ->get()
            ->sortBy(fn (Enrollment $e) => [$e->course?->name ?? '~', $e->parallel?->name ?? '~', AcademicYearExport::sortKey($e->student)])
            ->groupBy(fn (Enrollment $e) => ($e->course?->name ?? 'Sin curso') . ' · Paralelo ' . ($e->parallel?->name ?? 'sin paralelo'))
            ->map(fn ($items) => $items->values()->map(fn (Enrollment $e) => (object) [
                'name' => $e->student?->name ?? 'Estudiante #' . $e->student_id,
                'status' => $e->status,
                'grades' => $gradesByStudent[$e->student_id] ?? 0,
                'attendance' => $attendanceByStudent[$e->student_id] ?? 0,
            ]));

        // Los totales son de toda la gestión; los filtros solo cambian la lista de estudiantes.
        $totals = [
            'enrollments' => $allEnrollments->count(),
            'grades' => (int) $gradesByStudent->sum(),
            'attendance' => (int) $attendanceByStudent->sum(),
            'activities' => $activities,
        ];
        $shown = $groups->sum(fn ($g) => $g->count());
        $filtered = (bool) ($courseId || $parallelId);

        return view('admin.years.show', compact('year', 'groups', 'totals', 'courses', 'parallels', 'shown', 'filtered'));
    }

    /** Descarga el respaldo de la gestión en Excel (matrículas, notas y asistencia). Queda en la auditoría. */
    public function export(Request $request, AcademicYear $year)
    {
        AuditLog::log('year.export', 'academic_year', $year->id, ['year' => $year->year, 'closed' => $year->isClosed()], $request);

        return Excel::download(new AcademicYearExport($year), "gestion_{$year->year}_respaldo_" . now()->format('Ymd_His') . '.xlsx');
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
