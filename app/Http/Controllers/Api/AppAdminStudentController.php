<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Parallel;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CRUD de estudiantes por la API (solo administrador): listar, crear, mostrar, actualizar y borrar.
 * Mismas reglas que el formulario web. Borrar es una eliminación lógica (soft delete): el estudiante
 * se puede restaurar desde la web.
 */
class AppAdminStudentController extends Controller
{
    private const NAME = 'regex:/^[\pL ]+$/u';

    /** GET /admin/students?q=&parallel_id=&page=&per_page= */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $page = Student::with(['course', 'parallel'])
            ->active()
            ->when(trim((string) $request->query('q')), fn ($q, $term) => $q->searchName($term))
            ->when((int) $request->query('parallel_id'), fn ($q, $id) => $q->where('parallel_id', $id))
            ->orderedForList()
            ->paginate($perPage);

        return response()->json([
            'data' => $page->getCollection()->map(fn (Student $s) => $this->item($s)),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    /** POST /admin/students */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(), $this->messages());
        $parallel = Parallel::with('course')->findOrFail($data['parallel_id']);
        $this->ensureCapacity($parallel);

        $student = Student::create($data + [
            'course' => $parallel->course?->name,
            'course_id' => $parallel->course_id,
            'status' => 'activo',
            'registration_date' => now()->toDateString(),
        ]);
        AuditLog::log('student.create', 'student', $student->id, ['via' => 'api'], $request);

        return response()->json(['message' => 'Estudiante creado.', 'data' => $this->item($student->fresh(['course', 'parallel']))], 201);
    }

    /** GET /admin/students/{id} */
    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->item(Student::with(['course', 'parallel'])->findOrFail($id))]);
    }

    /** PUT /admin/students/{id}: se envían los datos que cambian (los demás se conservan). */
    public function update(Request $request, int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $data = $request->validate(collect($this->rules())->map(fn ($rule) => array_merge(['sometimes'], (array) $rule))->all(), $this->messages());

        if (isset($data['parallel_id']) && (int) $data['parallel_id'] !== (int) $student->parallel_id) {
            $parallel = Parallel::with('course')->findOrFail($data['parallel_id']);
            $this->ensureCapacity($parallel);
            $data += ['course' => $parallel->course?->name, 'course_id' => $parallel->course_id];
        }

        $student->update($data);
        AuditLog::log('student.update', 'student', $student->id, ['via' => 'api', 'fields' => array_keys($data)], $request);

        return response()->json(['message' => 'Estudiante actualizado.', 'data' => $this->item($student->fresh(['course', 'parallel']))]);
    }

    /** DELETE /admin/students/{id} */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $student = Student::findOrFail($id);
        $student->delete();
        AuditLog::log('student.delete', 'student', $student->id, ['via' => 'api'], $request);

        return response()->json(['message' => 'Estudiante eliminado. Se puede restaurar desde la web.']);
    }

    private function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:100', self::NAME],
            'second_last_name' => ['nullable', 'string', 'max:100', self::NAME],
            'first_name' => ['required', 'string', 'max:100', self::NAME],
            'age' => ['required', 'integer', 'min:3', 'max:25'],
            'gender' => ['required', Rule::in(['Masculino', 'Femenino'])],
            'level' => ['required', Rule::in(['básico', 'intermedio', 'avanzado'])],
            'parallel_id' => ['required', 'integer', 'exists:parallels,id'],
        ];
    }

    private function messages(): array
    {
        return [
            'last_name.required' => 'El apellido paterno es obligatorio.',
            'first_name.required' => 'Los nombres son obligatorios.',
            'last_name.regex' => 'El apellido solo puede tener letras y espacios.',
            'second_last_name.regex' => 'El apellido solo puede tener letras y espacios.',
            'first_name.regex' => 'Los nombres solo pueden tener letras y espacios.',
            'gender.in' => 'El género debe ser Masculino o Femenino.',
            'level.in' => 'El nivel debe ser básico, intermedio o avanzado.',
            'parallel_id.exists' => 'El paralelo no existe.',
        ];
    }

    private function ensureCapacity(Parallel $parallel): void
    {
        if ($parallel->students()->active()->count() >= $parallel->max_students) {
            throw ValidationException::withMessages(['parallel_id' => 'Este paralelo ya alcanzó su capacidad máxima de estudiantes.']);
        }
    }

    private function item(Student $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'last_name' => $s->last_name,
            'second_last_name' => $s->second_last_name,
            'first_name' => $s->first_name,
            'age' => $s->age,
            'gender' => $s->gender,
            'level' => $s->level,
            'course' => $s->course_name,
            'parallel_id' => $s->parallel_id,
            'parallel' => $s->parallel?->name,
            'status' => $s->status,
        ];
    }
}
