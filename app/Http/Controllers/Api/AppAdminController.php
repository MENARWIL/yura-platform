<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentReport;
use App\Services\UserAdminException;
use App\Services\UserAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Administración desde la app (solo rol admin). Las reglas son las de la web,
 * a través de UserAdminService: validación, protección de la propia cuenta, sesiones y auditoría.
 */
class AppAdminController extends Controller
{
    public function __construct(private readonly UserAdminService $users)
    {
    }

    public function overview(): JsonResponse
    {
        $col = $this->statusColumn();

        $recent = Student::with(['course', 'parallel'])->latest()->take(5)->get()->map(fn (Student $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'course' => $s->course_name,
            'parallel' => $s->parallel?->name,
        ]);

        return response()->json(['data' => [
            'students' => Student::count(),
            'teachers' => User::ofRole('profesor')->count(),
            'families' => User::ofRole(['padre', 'madre', 'tutor'])->count(),
            'users_active' => User::query()->whereIn($col, ['active', 'activo'])->count(),
            'users_suspended' => User::query()->whereNotIn($col, ['active', 'activo'])->count(),
            'recent_students' => $recent,
        ]]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->orderBy('name');
        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }
        if ($term = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        }
        if ($role = $request->query('role')) {
            $query->ofRole($role);
        }
        if ($status = $request->query('status')) {
            $status === 'active'
                ? $query->whereIn($this->statusColumn(), ['active', 'activo'])
                : $query->whereNotIn($this->statusColumn(), ['active', 'activo']);
        }

        $page = $query->paginate(max(1, min(50, (int) $request->query('per_page', 20))));

        return response()->json([
            'data' => $page->getCollection()->map(fn (User $u) => $this->item($u, $request->user()))->values(),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        return response()->json(['data' => $this->item($user, $request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $user = $this->users->create($request->user(), [
            'name' => $this->fullName($data),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'second_last_name' => $data['second_last_name'],
            'email' => $data['email'],
            'password' => $this->users->hashPassword($data['password']),
            'rol' => $data['rol'],
            'telefono' => $data['telefono'] ?? null,
            'estado' => 'activo',
            'main_course_id' => $data['rol'] === 'profesor' ? ($data['main_course_id'] ?? null) : null,
        ]);

        return response()->json(['data' => $this->item($user, $request->user())], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $this->validated($request, $user);

        $attributes = [
            'name' => $this->fullName($data),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'second_last_name' => $data['second_last_name'],
            'email' => $data['email'],
            'rol' => $data['rol'],
            'telefono' => $data['telefono'] ?? null,
            'main_course_id' => $data['rol'] === 'profesor' ? ($data['main_course_id'] ?? null) : null,
        ];
        if (! empty($data['password'])) {
            $attributes['password'] = $this->users->hashPassword($data['password']);
        }

        try {
            $this->users->update($request->user(), $user, $attributes);
        } catch (UserAdminException $e) {
            return $this->rejected($e);
        }

        return response()->json(['data' => $this->item($user->fresh(), $request->user())]);
    }

    public function status(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        try {
            $this->users->toggleStatus($request->user(), $user);
        } catch (UserAdminException $e) {
            return $this->rejected($e);
        }

        return response()->json(['data' => $this->item($user->fresh(), $request->user())]);
    }

    public function destroy(Request $request, int $id): Response|JsonResponse
    {
        $user = User::findOrFail($id);

        try {
            $this->users->delete($request->user(), $user);
        } catch (UserAdminException $e) {
            return $this->rejected($e);
        }

        return response()->noContent();
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $user = User::onlyTrashed()->findOrFail($id);
        $this->users->restore($request->user(), $user);

        return response()->json(['data' => $this->item($user->fresh(), $request->user())]);
    }

    private function validated(Request $request, ?User $editing = null): array
    {
        app()->setLocale('es');
        $name = ['required', 'string', 'max:100', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'];
        $request->merge([
            'first_name' => preg_replace('/[ \t]+/u', ' ', trim((string) $request->input('first_name'))),
            'last_name' => preg_replace('/[ \t]+/u', ' ', trim((string) $request->input('last_name'))),
            'second_last_name' => preg_replace('/[ \t]+/u', ' ', trim((string) $request->input('second_last_name'))),
            'email' => strtolower(trim((string) $request->input('email'))),
            'telefono' => trim((string) $request->input('telefono')),
        ]);

        return $request->validate([
            'first_name' => $name,
            'last_name' => $name,
            'second_last_name' => $name,
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($editing?->id)],
            'password' => [$editing ? 'nullable' : 'required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'rol' => ['required', Rule::in(UserAdminService::ROLES)],
            'main_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
        ], $this->messages());
    }

    /** Los mismos textos que la web (resources/lang/es/messages.php). */
    private function messages(): array
    {
        $required = __('messages.validation.required');
        $password = __('messages.validation.password');

        return [
            'first_name.required' => $required, 'first_name.regex' => __('messages.validation.name'),
            'last_name.required' => $required, 'last_name.regex' => __('messages.validation.name'),
            'second_last_name.required' => $required, 'second_last_name.regex' => __('messages.validation.name'),
            'email.required' => $required, 'email.email' => __('messages.validation.email'),
            'email.unique' => __('messages.validation.unique'),
            'rol.required' => $required, 'rol.in' => __('messages.validation.invalid'),
            'main_course_id.integer' => __('messages.validation.integer'),
            'main_course_id.exists' => __('messages.validation.invalid'),
            'telefono.regex' => __('messages.validation.phone'),
            'password.required' => $required, 'password.min' => $password, 'password.letters' => $password,
            'password.mixed' => $password, 'password.numbers' => $password, 'password.symbols' => $password,
        ];
    }

    private function statusColumn(): string
    {
        return Schema::hasColumn('users', 'status') ? 'status' : 'estado';
    }

    private function fullName(array $data): string
    {
        return trim(preg_replace('/\s+/', ' ', $data['last_name'] . ' ' . $data['second_last_name'] . ' ' . $data['first_name']));
    }

    private function item(User $user, User $viewer): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'second_last_name' => $user->second_last_name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => in_array(strtolower(trim((string) $user->status)), ['active', 'activo'], true) ? 'active' : 'inactive',
            'telefono' => $user->phone ?? $user->telefono,
            'main_course_id' => $user->main_course_id,
            'deleted_at' => $user->deleted_at?->toISOString(),
            'is_self' => $user->is($viewer),
        ];
    }

    private function rejected(UserAdminException $e): JsonResponse
    {
        return response()->json(['message' => $e->getMessage()], 422);
    }
}
