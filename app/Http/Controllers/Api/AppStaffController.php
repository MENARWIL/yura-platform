<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Parallel;
use App\Models\RobotActivity;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Services\StudentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lectura para administrador y profesor: estudiantes y actividades del robot.
 * El profesor solo ve lo suyo; ninguna ruta de aquí escribe datos.
 */
class AppStaffController extends Controller
{
    public function students(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $this->perPage($request);

        $query = Student::with(['course', 'parallel', 'profesor', 'grades.subject', 'attendances'])->orderBy('name');
        if ($user->isProfesor()) {
            $query->where($query->getModel()->getTeacherUserForeignKey(), $user->id);
        }
        if ($term = trim((string) $request->query('q'))) {
            $query->where('name', 'like', '%' . $term . '%');
        }
        $courseId = (int) $request->query('course_id');
        $parallelId = (int) $request->query('parallel_id');
        if ($yearId = (int) $request->query('year_id')) {
            // Una gestión concreta: cuenta la matrícula de ese año, no el curso actual del estudiante.
            $query->whereHas('enrollments', function ($q) use ($yearId, $courseId, $parallelId) {
                $q->where('academic_year_id', $yearId)
                    ->when($courseId, fn ($e) => $e->where('course_id', $courseId))
                    ->when($parallelId, fn ($e) => $e->where('parallel_id', $parallelId));
            });
        } else {
            $courseId && $query->where('course_id', $courseId);
            $parallelId && $query->where('parallel_id', $parallelId);
        }

        $page = $query->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Student $s) => StudentReport::listItem($s))->values(),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    /** Gestiones escolares, de la más reciente a la más antigua. La actual va marcada. */
    public function years(): JsonResponse
    {
        return response()->json(['data' => AcademicYear::orderByDesc('year')->get()->map(fn (AcademicYear $y) => [
            'id' => $y->id,
            'year' => $y->year,
            'name' => $y->name,
            'is_current' => $y->is_current,
        ])->values()]);
    }

    /**
     * Cursos con sus paralelos para armar los filtros. El administrador ve todos;
     * el profesor, solo los paralelos donde tiene estudiantes o una asignación docente.
     */
    public function courses(Request $request): JsonResponse
    {
        $user = $request->user();
        $parallels = Parallel::with('course')->orderBy('name');

        if ($user->isProfesor()) {
            $teacherKey = (new Student())->getTeacherUserForeignKey();
            $ids = Student::where($teacherKey, $user->id)->pluck('parallel_id')
                ->merge(TeachingAssignment::where('teacher_user_id', $user->id)->where('active', true)->pluck('parallel_id'))
                ->filter()->unique()->values();
            $parallels->whereIn('id', $ids);
        }

        $courses = $parallels->get()->groupBy('course_id')->map(fn ($group) => [
            'id' => $group->first()->course_id,
            'name' => $group->first()->course?->name,
            'parallels' => $group->map(fn (Parallel $p) => ['id' => $p->id, 'name' => $p->name])->values(),
        ])->sortBy('name')->values();

        return response()->json(['data' => $courses]);
    }

    public function summary(Request $request, Student $student): JsonResponse
    {
        if (! $request->user()->canViewStudent($student)) {
            return response()->json(['message' => 'No tienes permiso para ver este estudiante.'], 403);
        }

        $student->load(['course', 'parallel', 'profesor', 'grades.subject', 'attendances']);

        return response()->json(['data' => StudentReport::identity($student) + ['report' => StudentReport::forApp($student)]]);
    }

    public function robotActivities(Request $request): JsonResponse
    {
        $allowed = ['pending', 'in_progress', 'completed', 'partial', 'failed', 'cancelled'];
        $status = $request->query('status');
        if ($status !== null && ! in_array($status, $allowed, true)) {
            return response()->json(['message' => 'Estado no válido.', 'errors' => ['status' => ['Estado no válido.']]], 422);
        }

        $page = RobotActivity::query()
            ->with(['parallel', 'subject'])
            ->withCount('attempts')
            ->where('teacher_user_id', $request->user()->id)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when((int) $request->query('parallel_id'), fn ($q, $id) => $q->where('parallel_id', $id))
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json([
            'data' => $page->getCollection()->map(fn (RobotActivity $a) => [
                'id' => $a->id,
                'status' => $a->status,
                'category' => data_get($a->payload, 'activity.content.category'),
                'quarter' => $a->quarter,
                'activity_number' => $a->activity_number,
                'parallel' => $a->parallel?->name,
                'subject' => $a->subject?->name,
                'attempts' => $a->attempts_count,
                'groups' => count((array) data_get($a->payload, 'activity.groups', [])),
                'created_at' => $a->created_at?->toISOString(),
                'completed_at' => $a->completed_at?->toISOString(),
            ])->values(),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    private function perPage(Request $request): int
    {
        return max(1, min(50, (int) $request->query('per_page', 20)));
    }
}
