<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Models\AuditLog;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    /** Tamaños de página que se pueden elegir en la lista de estudiantes. */
    private const PER_PAGE_OPTIONS = [5, 10, 15, 20];

    private const PER_PAGE_DEFAULT = 5;

    public function index(Request $request)
    {
        $filters = $request->validate([
            'student_name' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'parallel_id' => ['nullable', 'integer', 'exists:parallels,id'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
        ]);

        // Buscador vacío = sin filtro de nombre: la lista vuelve completa.
        $studentName = preg_replace('/\s+/', ' ', trim((string) ($filters['student_name'] ?? ''))) ?: null;
        $perPage = (int) ($filters['per_page'] ?? self::PER_PAGE_DEFAULT);
        $user = Auth::user();

        $parentForeignKey = (new Student())->getParentUserForeignKey();

        $students = Student::with(['course', 'parallel', 'parent', 'teacher'])
            ->when($user->isProfesor(), function ($query) use ($user) {
                $query->taughtBy($user);
            })
            ->when($user->isResponsibleRole(), function ($query) use ($user, $parentForeignKey) {
                $query->where(function ($q) use ($user, $parentForeignKey) {
                    $q->where($parentForeignKey, $user->id)
                        ->orWhereHas('familiares', function ($family) use ($user) {
                            $family->where('user_id', $user->id);
                        });
                });
            })
            ->when($studentName, function ($query, $studentName) {
                // Busca en nombre completo, nombres y apellidos (ej. "Quispe" o "Ana Quispe").
                $like = '%' . $studentName . '%';
                $query->where(function ($q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('second_last_name', 'like', $like);
                });
            })
            ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                $query->where('course_id', $courseId);
            })
            ->when($filters['parallel_id'] ?? null, function ($query, $parallelId) {
                $query->where('parallel_id', $parallelId);
            })
            ->orderByRaw("CASE WHEN last_name IS NULL OR last_name = '' THEN 1 ELSE 0 END")
            ->orderBy('last_name')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        // Calculate real average from grades for each student
        foreach ($students as $student) {
            $grades = $student->grades()->where('status', 'completed')->pluck('score')->filter(function ($v) {
                return is_numeric($v);
            })->map(function ($v) {
                return (float) $v;
            })->all();

            if (count($grades)) {
                $student->promedio_real = round(array_sum($grades) / count($grades), 2);
            } else {
                $student->promedio_real = '-';
            }
        }

        $courses = \App\Models\Course::orderBy('name')->get();
        $parallels = Parallel::with('course')->orderBy('name')->get();

        // El profesor solo ve en los filtros (y en Exportar) los cursos y paralelos que atiende.
        if ($user->isProfesor()) {
            $teacherParallelIds = Student::taughtBy($user)->whereNotNull('parallel_id')->distinct()->pluck('parallel_id')
                ->merge(Student::assignedParallelIds($user))
                ->map(fn ($id) => (int) $id)
                ->unique();
            $parallels = $parallels->filter(fn ($parallel) => $teacherParallelIds->contains((int) $parallel->id))->values();
            $courses = $courses->whereIn('id', $parallels->pluck('course_id')->unique())->values();
        }

        return view('students.index', compact(
            'students',
            'courses',
            'parallels'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isAcademic()) {
            abort(403, __('messages.unauthorized'));
        }

        $courses = \App\Models\Course::all();
        $parallels = \App\Models\Parallel::all();
        $tutors = User::where('role', 'tutor')
            ->whereIn('status', ['active', 'activo'])
            ->orderBy('name')
            ->get();
        $teachers = User::whereIn('role', ['profesor', 'teacher'])
            ->whereIn('status', ['active', 'activo'])
            ->orderBy('name')
            ->get();

        return view('students.create', [
            'courses' => $courses,
            'parallels' => $parallels,
            'tutors' => $tutors,
            'teachers' => $teachers,
            'newTutorId' => request()->integer('new_tutor_id'),
            'newTutorTarget' => request()->input('target', 'primary'),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isAcademic()) {
            abort(403, __('messages.unauthorized'));
        }

        $request = $this->normalizeStudentFields($request);
        $request->merge(['registration_date' => now()->toDateString()]);

        $rules = [
            'last_name' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'second_last_name' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'first_name' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'age' => 'required|integer|min:0|max:120',
            'gender' => 'required|in:Masculino,Femenino',
            'level' => 'required|in:básico,intermedio,avanzado',
            'registration_date' => 'required|date|before_or_equal:today',
            'course_id' => ['nullable', 'integer', 'min:0', 'exists:courses,id'],
            'parallel_id' => ['required', 'integer', 'min:0', 'exists:parallels,id'],
            'parent_user_id' => ['nullable', 'integer', 'min:0', Rule::exists('users', 'id')->where(function ($query) {
                $query->whereIn('role', ['padre', 'madre', 'tutor'])
                    ->whereIn('status', ['active', 'activo']);
            })],
            'secondary_parent_user_id' => ['nullable', 'integer', 'min:0', 'different:parent_user_id', Rule::exists('users', 'id')->where(function ($query) {
                $query->whereIn('role', ['padre', 'madre', 'tutor'])
                    ->whereIn('status', ['active', 'activo']);
            })],
            'teacher_user_id' => ['nullable', 'integer', 'min:0', Rule::exists('users', 'id')->where(function ($query) {
                $query->whereIn('role', ['profesor', 'teacher'])
                    ->whereIn('status', ['active', 'activo']);
            })],
            'score' => 'nullable|integer|min:0|max:100',
            'writing_score' => 'nullable|integer|min:0|max:100',
            'exam_score' => 'nullable|integer|min:0|max:100',
            'attendance' => 'nullable|integer|min:0|max:100',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        $data = $request->validate($rules, $this->studentValidationMessages());
        $data['name'] = trim(preg_replace('/\s+/', ' ', $data['last_name'] . ' ' . $data['second_last_name'] . ' ' . $data['first_name']));
        $parallel = Parallel::with('course')->findOrFail($data['parallel_id']);

        if (! empty($data['course_id']) && (int) $data['course_id'] !== (int) $parallel->course_id) {
            return redirect()->back()
                ->withErrors(['parallel_id' => 'El paralelo seleccionado no pertenece al curso indicado.'])
                ->withInput();
        }

        if ($parallel->students()->count() >= $parallel->max_students) {
            return redirect()->back()
                ->withErrors(['parallel_id' => 'Este paralelo ha alcanzado su capacidad máxima de estudiantes'])
                ->withInput();
        }

        $data['course'] = $parallel->course->name;
        $data['course_id'] = $parallel->course_id;
        $data['parallel_id'] = $parallel->id;
        $secondaryTutorId = $data['secondary_parent_user_id'] ?? null;
        unset($data['secondary_parent_user_id']);

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('students', 'public');
        }

        $student = Student::create($data);

        $familyUser = User::find($data['parent_user_id'] ?? null);
        if ($familyUser) {
            $student->familiares()->syncWithoutDetaching([
                $familyUser->id => [
                    'relation_type' => $this->resolveFamilyRelationType($familyUser),
                    'is_primary' => true,
                    'can_view' => true,
                    'can_edit' => false,
                    'can_receive_reports' => true,
                    'active' => true,
                ],
            ]);
        }

        $secondaryFamilyUser = User::find($secondaryTutorId);
        if ($secondaryFamilyUser) {
            $student->familiares()->syncWithoutDetaching([
                $secondaryFamilyUser->id => [
                    'relation_type' => $this->resolveFamilyRelationType($secondaryFamilyUser),
                    'is_primary' => false,
                    'can_view' => true,
                    'can_edit' => false,
                    'can_receive_reports' => true,
                    'active' => true,
                ],
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Estudiante creado con éxito.');
    }

    public function show(Student $student)
    {
        $this->authorizeAccess($student);

        // Load completed grades and compute accumulated average for the student
        $grades = $student->grades()->where('status', 'completed')->get();
        $student->promedio_acumulado = count($grades) ? round($grades->avg('score'), 2) : 0;

        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $this->authorizeAccess($student, true);
        $padres = User::whereIn('role', ['padre', 'madre', 'tutor'])
            ->whereIn('status', ['active', 'activo'])
            ->orderBy('name')
            ->get();
        $profesores = User::whereIn('role', ['profesor', 'teacher'])
            ->whereIn('status', ['active', 'activo'])
            ->orderBy('name')
            ->get();
        $parallels = Parallel::with('course')->get();

        return view('students.edit', compact('student', 'padres', 'profesores', 'parallels'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeAccess($student, true);

        $request = $this->normalizeStudentFields($request);

        $studentModel = new Student();
        $parentForeignKey = $studentModel->getParentUserForeignKey();
        $teacherForeignKey = $studentModel->getTeacherUserForeignKey();

        $rules = [
            'last_name' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'second_last_name' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'first_name' => 'required|string|max:100|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'age' => 'required|integer|min:0|max:120',
            'gender' => 'required|in:Masculino,Femenino',
            'level' => 'required|in:básico,intermedio,avanzado',
            'registration_date' => 'sometimes|nullable|date|before_or_equal:today',
            'status' => 'required|in:activo,inactivo',
            'parallel_id' => ['required', 'integer', 'min:0', 'exists:parallels,id'],
            $parentForeignKey => ['nullable', 'integer', 'min:0', Rule::exists('users', 'id')->where(function ($query) {
                $query->whereIn('role', ['padre', 'madre', 'tutor'])
                    ->whereIn('status', ['active', 'activo']);
            })],
            $teacherForeignKey => ['nullable', 'integer', 'min:0', Rule::exists('users', 'id')->where(function ($query) {
                $query->whereIn('role', ['profesor', 'teacher'])
                    ->whereIn('status', ['active', 'activo']);
            })],
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        $data = $request->validate($rules, $this->studentValidationMessages());
        $data['name'] = trim(preg_replace('/\s+/', ' ', $data['last_name'] . ' ' . $data['second_last_name'] . ' ' . $data['first_name']));
        $parallel = Parallel::with('course')->findOrFail($data['parallel_id']);

        if ($parallel->id !== $student->parallel_id && $parallel->students()->count() >= $parallel->max_students) {
            return redirect()->back()
                ->withErrors(['parallel_id' => 'Este paralelo ha alcanzado su capacidad máxima de estudiantes'])
                ->withInput();
        }

        $data['course'] = $parallel->course->name;
        $data['course_id'] = $parallel->course_id;
        $data['parallel_id'] = $parallel->id;

        if ($request->hasFile('foto')) {
            $newPhotoPath = $request->file('foto')->store('students', 'public');

            if ($student->foto_path) {
                Storage::disk('public')->delete($student->foto_path);
            }

            $data['foto_path'] = $newPhotoPath;
        }

        $student->update($data);

        StudentFamilyMember::where('student_id', $student->id)
            ->update(['is_primary' => false]);

        $familyUser = User::find($data[$parentForeignKey] ?? null);
        if ($familyUser) {
            $student->familiares()->syncWithoutDetaching([
                $familyUser->id => [
                    'relation_type' => $this->resolveFamilyRelationType($familyUser),
                    'is_primary' => true,
                    'can_view' => true,
                    'can_edit' => false,
                    'can_receive_reports' => true,
                    'active' => true,
                ],
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Estudiante actualizado con éxito.');
    }

    /**
     * Completa los datos del estudiante que faltan (nivel, edad, género) desde su ficha.
     * Un campo vacío no borra lo que ya está registrado. El profesor no se toca aquí:
     * se asigna en otra pantalla.
     */
    public function updateInfo(Request $request, Student $student): RedirectResponse
    {
        $actor = Auth::user();
        abort_unless($actor->isAdmin() || $actor->isAcademic(), 403, 'No tienes permiso para editar este estudiante.');

        $data = $request->validateWithBag('info', [
            'level' => ['nullable', 'in:básico,intermedio,avanzado'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', 'in:Masculino,Femenino'],
        ], [
            'level.in' => 'Elige un nivel de la lista (Qallariy, Chawpi o Sumaq).',
            'age.integer' => 'La edad debe ser un número entero.',
            'age.min' => 'La edad no puede ser negativa.',
            'age.max' => 'La edad no puede ser mayor a 120.',
            'gender.in' => 'Elige Masculino o Femenino.',
        ]);

        $changes = [];
        foreach (['level', 'age', 'gender'] as $field) {
            if (filled($data[$field] ?? null)) {
                $changes[$field] = $data[$field];
            }
        }

        $student->update($changes);
        $changed = array_keys($student->getChanges());

        if ($changed) {
            AuditLog::log('student_info_updated', 'student', $student->id, ['fields' => $changed], $request);
        }

        return redirect()->route('grades.show', $student)
            ->with('success', $changed ? 'Información del estudiante actualizada.' : 'No había cambios que guardar.');
    }

    /**
     * Asigna un responsable (padre, madre o tutor) a un estudiante desde su ficha.
     * Sirve cuando el estudiante se registró sin tutor: se elige uno ya registrado
     * (mode=existing) o se crea uno nuevo en el momento (mode=new).
     */
    public function assignGuardian(Request $request, Student $student): RedirectResponse
    {
        $actor = Auth::user();
        abort_unless($actor->isAdmin() || $actor->isAcademic(), 403, 'No tienes permiso para asignar responsables.');

        $mode = $request->input('mode') === 'new' ? 'new' : 'existing';
        $collapse = fn ($key) => $request->input($key) !== null ? preg_replace('/\s+/', ' ', trim($request->input($key))) : null;
        $messages = [
            'user_id.required' => 'Elige un responsable de la lista.',
            'user_id.exists' => 'El responsable debe ser un usuario activo con rol de padre, madre o tutor.',
            'relation_type.required' => 'Indica la relación con el estudiante.',
            'relation_type.in' => __('messages.validation.invalid'),
            'ci.required' => __('messages.validation.required'),
            'ci.unique' => __('messages.validation.unique'),
            'last_name.required' => __('messages.validation.required'),
            'last_name.regex' => __('messages.validation.name'),
            'second_last_name.required' => __('messages.validation.required'),
            'second_last_name.regex' => __('messages.validation.name'),
            'first_name.required' => __('messages.validation.required'),
            'first_name.regex' => __('messages.validation.name'),
            'phone.required' => __('messages.validation.required'),
            'phone.regex' => __('messages.validation.phone'),
            'email.required' => __('messages.validation.required'),
            'email.email' => __('messages.validation.email'),
            'email.unique' => __('messages.validation.unique'),
            'password.required' => __('messages.validation.required'),
            'password.confirmed' => __('messages.validation.password_confirm'),
            'password.min' => __('messages.validation.password'),
            'password.letters' => __('messages.validation.password'),
            'password.mixed' => __('messages.validation.password'),
            'password.numbers' => __('messages.validation.password'),
            'password.symbols' => __('messages.validation.password'),
        ];

        if ($mode === 'new') {
            $request->merge([
                'ci' => $collapse('ci'),
                'last_name' => $collapse('last_name'),
                'second_last_name' => $collapse('second_last_name'),
                'first_name' => $collapse('first_name'),
                'phone' => $collapse('phone'),
                'email' => $request->input('email') !== null ? strtolower(trim($request->input('email'))) : null,
            ]);

            $data = $request->validateWithBag('guardian', [
                'ci' => ['required', 'string', 'max:50', 'regex:/^[0-9A-Za-z-]+$/', Rule::unique('users', 'ci')],
                'last_name' => ['required', 'string', 'max:100', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
                'second_last_name' => ['required', 'string', 'max:100', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
                'first_name' => ['required', 'string', 'max:100', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
                'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
                'password' => ['required', 'confirmed', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
                'relation_type' => ['required', 'in:padre,madre,tutor'],
                'is_primary' => ['nullable', 'boolean'],
            ], $messages);
        } else {
            $data = $request->validateWithBag('guardian', [
                'user_id' => ['required', Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', ['padre', 'madre', 'tutor'])->whereIn('status', ['active', 'activo']);
                })],
                'relation_type' => ['required', 'in:padre,madre,tutor'],
                'is_primary' => ['nullable', 'boolean'],
            ], $messages);
        }

        $familyUser = DB::transaction(function () use ($data, $mode, $student, $request) {
            if ($mode === 'new') {
                $familyUser = User::create([
                    'ci' => $data['ci'],
                    'last_name' => $data['last_name'],
                    'second_last_name' => $data['second_last_name'],
                    'first_name' => $data['first_name'],
                    'name' => trim($data['last_name'] . ' ' . $data['second_last_name'] . ' ' . $data['first_name']),
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['relation_type'],
                    'status' => 'active',
                ]);
            } else {
                $familyUser = User::findOrFail($data['user_id']);
            }

            $parentKey = $student->getParentUserForeignKey();
            // Quien ya es el responsable principal no se degrada por desmarcar la casilla.
            $primary = $request->boolean('is_primary', true) || (int) $student->getAttribute($parentKey) === (int) $familyUser->id;

            if ($primary) {
                StudentFamilyMember::where('student_id', $student->id)->update(['is_primary' => false]);
                $student->update([$parentKey => $familyUser->id]);
            }

            $student->familiares()->syncWithoutDetaching([
                $familyUser->id => [
                    'relation_type' => $data['relation_type'],
                    'is_primary' => $primary,
                    'can_view' => true,
                    'can_edit' => false,
                    'can_receive_reports' => true,
                    'active' => true,
                ],
            ]);

            AuditLog::log('guardian_assigned', 'student', $student->id, [
                'guardian_user_id' => $familyUser->id,
                'relation_type' => $data['relation_type'],
                'is_primary' => $primary,
                'guardian_created' => $mode === 'new',
            ], $request);

            return $familyUser;
        });

        return redirect()->route('grades.show', $student)
            ->with('success', 'Responsable asignado: ' . $familyUser->name . '.');
    }

    public function destroy(Student $student)
    {
        $this->authorizeAccess($student, true);

        $student->delete();

        return redirect()->route('students.index')->with('success', 'Estudiante eliminado con éxito.');
    }

    public function restore($id)
    {
        $student = Student::withTrashed()->findOrFail($id);
        $user = Auth::user();

        if (! $student->trashed()) {
            return back()->with('error', 'El estudiante ya está activo.');
        }

        if ($user->isAdmin()) {
            $student->restore();

            return redirect()->route('students.index')->with('success', 'Estudiante restaurado con éxito.');
        }

        $teacherForeignKey = $student->getTeacherUserForeignKey();

        if ($user->isProfesor() && $student->getAttribute($teacherForeignKey) == $user->id) {
            $student->restore();

            return redirect()->route('students.index')->with('success', 'Estudiante restaurado con éxito.');
        }

        abort(403, 'No tienes permiso para restaurar este estudiante.');
    }

    public function forceDestroy($id)
    {
        if (! Auth::user()->isAdmin()) {
            abort(403, 'Solo el administrador puede eliminar permanentemente este estudiante.');
        }

        $student = Student::withTrashed()->findOrFail($id);

        if ($student->foto_path) {
            Storage::disk('public')->delete($student->foto_path);
        }

        $student->forceDelete();

        return redirect()->route('students.index')->with('success', 'Estudiante eliminado permanentemente.');
    }

    private function normalizeStudentFields(Request $request): Request
    {
        foreach (['first_name', 'last_name', 'second_last_name'] as $namePart) {
            if ($request->exists($namePart)) {
                $request->merge([$namePart => preg_replace('/\s+/', ' ', trim((string) $request->input($namePart)))]);
            }
        }

        $aliases = [
            'name' => ['nombre'],
            'age' => ['edad'],
            'gender' => ['genero'],
            'level' => ['nivel'],
            'registration_date' => ['fecha_registro'],
            'course_id' => ['curso_id'],
            'parallel_id' => ['parallel_id'],
            'parent_user_id' => ['usuario_id'],
            'teacher_user_id' => ['profesor_id'],
            'score' => ['puntaje'],
            'writing_score' => ['nota_escritura'],
            'exam_score' => ['nota_examen'],
            'attendance' => ['asistencia'],
            'status' => ['estado'],
        ];

        foreach ($aliases as $canonical => $legacyKeys) {
            if ($request->exists($canonical)) {
                continue;
            }

            foreach ($legacyKeys as $legacyKey) {
                if ($request->exists($legacyKey)) {
                    $request->merge([$canonical => $request->input($legacyKey)]);
                    break;
                }
            }
        }

        return $request;
    }

    private function studentValidationMessages(): array
    {
        return [
            'name.required' => 'El nombre completo es obligatorio.',
            'name.string' => 'El nombre completo debe ser texto.',
            'name.max' => 'El nombre completo no puede superar los 255 caracteres.',
            'name.regex' => 'El nombre solo puede contener letras y espacios.',
            'last_name.required' => 'El apellido paterno es obligatorio.',
            'last_name.regex' => 'El apellido paterno solo puede contener letras y espacios.',
            'last_name.max' => 'El apellido paterno no puede superar los 100 caracteres.',
            'second_last_name.required' => 'El apellido materno es obligatorio.',
            'second_last_name.regex' => 'El apellido materno solo puede contener letras y espacios.',
            'second_last_name.max' => 'El apellido materno no puede superar los 100 caracteres.',
            'first_name.required' => 'El nombre es obligatorio.',
            'first_name.regex' => 'El nombre solo puede contener letras y espacios.',
            'first_name.max' => 'El nombre no puede superar los 100 caracteres.',
            'age.required' => 'La edad es obligatoria.',
            'age.integer' => 'La edad debe ser un número entero.',
            'age.min' => 'La edad debe ser como mínimo 1 año.',
            'age.max' => 'La edad no puede superar los 120 años.',
            'gender.required' => 'Debe seleccionar el género.',
            'gender.in' => 'El género seleccionado no es válido.',
            'level.required' => 'Debe seleccionar el nivel educativo.',
            'level.in' => 'El nivel educativo seleccionado no es válido.',
            'registration_date.required' => 'La fecha de registro es obligatoria.',
            'registration_date.date' => 'La fecha de registro no es válida.',
            'status.required' => 'Debe seleccionar el estado del estudiante.',
            'status.in' => 'El estado seleccionado no es válido.',
            'course_id.required' => 'Debe seleccionar un curso.',
            'course_id.exists' => 'El curso seleccionado no existe.',
            'parallel_id.required' => 'Debe seleccionar un paralelo.',
            'parallel_id.exists' => 'El paralelo seleccionado no existe.',
            'parent_user_id.required' => 'Debe seleccionar un responsable principal.',
            'parent_user_id.exists' => 'El responsable debe ser un usuario activo con rol de padre, madre o tutor.',
            'usuario_id.required' => 'Debe seleccionar un responsable principal.',
            'usuario_id.exists' => 'El responsable debe ser un usuario activo con rol de padre, madre o tutor.',
            'teacher_user_id.exists' => 'El profesor seleccionado no existe o no está activo.',
            'profesor_id.exists' => 'El profesor seleccionado no existe o no está activo.',
            'score.numeric' => 'El puntaje debe ser numérico.',
            'score.min' => 'El puntaje no puede ser menor que 0.',
            'score.max' => 'El puntaje no puede superar 100.',
            'writing_score.numeric' => 'La nota de escritura debe ser numérica.',
            'writing_score.min' => 'La nota de escritura no puede ser menor que 0.',
            'writing_score.max' => 'La nota de escritura no puede superar 100.',
            'exam_score.numeric' => 'La nota del examen debe ser numérica.',
            'exam_score.min' => 'La nota del examen no puede ser menor que 0.',
            'exam_score.max' => 'La nota del examen no puede superar 100.',
            'attendance.numeric' => 'La asistencia debe ser numérica.',
            'attendance.min' => 'La asistencia no puede ser menor que 0.',
            'attendance.max' => 'La asistencia no puede superar 100.',
            'foto.image' => 'El archivo debe ser una imagen válida.',
            'foto.mimes' => 'La foto debe estar en formato JPG, PNG o WEBP.',
            'foto.max' => 'La foto no puede superar los 2 MB.',
            'tutors.*.name.string' => 'El nombre del tutor debe ser texto.',
            'tutors.*.name.max' => 'El nombre del tutor no puede superar los 255 caracteres.',
            'tutors.*.relation.required_with' => 'Debe indicar el parentesco cuando informa un tutor.',
            'tutors.*.relation.in' => 'El parentesco seleccionado no es válido.',
        ];
    }

    private function resolveFamilyRelationType(User $user): string
    {
        return match ($user->role) {
            'padre' => 'padre',
            'madre' => 'madre',
            default => 'tutor',
        };
    }

    private function authorizeAccess(Student $student, bool $manage = false)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isAcademic()) {
            return;
        }

        $teacherForeignKey = $student->getTeacherUserForeignKey();
        $studentUserForeignKey = $student->getStudentUserForeignKey();

        if ($user->isProfesor() && $student->getAttribute($teacherForeignKey) == $user->id) {
            return;
        }

        // El profesor de una asignación docente del paralelo puede consultar (no modificar) al estudiante.
        if (! $manage && $user->teachesStudent($student)) {
            return;
        }

        if ($user->isEstudiante() && $student->getAttribute($studentUserForeignKey) == $user->id) {
            if ($manage) {
                abort(403, 'Los estudiantes no pueden modificar su información desde este módulo.');
            }

            return;
        }

        if ($user->isFamilyOf($student)) {
            if ($manage) {
                abort(403, 'Los familiares no pueden modificar el registro del estudiante.');
            }

            return;
        }

        abort(403, 'No tienes permiso para acceder a este estudiante.');
    }

    public function exportExcel(Student $student = null)
    {
        if ($student) {
            $this->authorizeAccess($student);

            // Build a simple HTML table so Excel can open it
            $grades = $student->grades()->get();
            $html = view('students.exports.excel_table', compact('student', 'grades'))->render();

            return response($html, 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=utf-8',
                'Content-Disposition' => 'attachment; filename=reporte_estudiante_' . $student->id . '.xls',
            ]);
        }

        return Excel::download(new StudentsExport, 'reporte_estudiantes_' . date('Ymd_His') . '.xlsx');
    }

    public function exportPdf(Student $student = null)
    {
        if ($student) {
            $this->authorizeAccess($student);

            $grades = $student->grades()->get();
            $pdf = Pdf::loadView('students.print_pdf', compact('student', 'grades'));
            $pdf->setPaper('A4', 'portrait');
            return $pdf->stream('reporte_estudiante_' . $student->id . '.pdf');
        }

        $students = Student::with(['padre', 'profesor', 'course', 'parallel'])->get();
        $pdf = Pdf::loadView('reports.students_pdf', compact('students'));

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('reporte_general_estudiantes.pdf');
    }

    // Wrapper for explicit all-students Excel download route
    public function exportExcelAll(Request $request)
    {
        $filters = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'parallel_id' => ['nullable', 'integer', 'exists:parallels,id'],
        ]);

        $scopeSlug = $this->exportScopeSlug($filters['course_id'] ?? null, $filters['parallel_id'] ?? null);

        return Excel::download(
            new StudentsExport($filters['course_id'] ?? null, $filters['parallel_id'] ?? null),
            'reporte_estudiantes_' . $scopeSlug . '_' . date('Ymd_His') . '.xlsx'
        );
    }

    // Wrapper for explicit all-students PDF download route
    public function exportPdfAll(Request $request)
    {
        $filters = $request->validate([
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'parallel_id' => ['nullable', 'integer', 'exists:parallels,id'],
        ]);

        $students = Student::with(['padre', 'profesor', 'course', 'parallel'])
            ->when($filters['course_id'] ?? null, fn ($query, $courseId) => $query->where('course_id', $courseId))
            ->when($filters['parallel_id'] ?? null, fn ($query, $parallelId) => $query->where('parallel_id', $parallelId))
            ->get();

        $scopeLabel = $this->exportScopeLabel($filters['course_id'] ?? null, $filters['parallel_id'] ?? null);

        $pdf = Pdf::loadView('reports.students_pdf', compact('students', 'scopeLabel'));
        $pdf->setPaper('A4', 'portrait');

        $scopeSlug = $this->exportScopeSlug($filters['course_id'] ?? null, $filters['parallel_id'] ?? null);

        return $pdf->download('reporte_estudiantes_' . $scopeSlug . '_' . date('Ymd_His') . '.pdf');
    }

    private function exportScopeLabel(?int $courseId, ?int $parallelId): string
    {
        if (! $courseId) {
            return 'Toda la unidad educativa';
        }

        $course = \App\Models\Course::find($courseId);

        if (! $parallelId) {
            return 'Curso: ' . ($course->name ?? 'N/A');
        }

        $parallel = Parallel::find($parallelId);

        return 'Curso: ' . ($course->name ?? 'N/A') . ' - Paralelo: ' . ($parallel->name ?? 'N/A');
    }

    private function exportScopeSlug(?int $courseId, ?int $parallelId): string
    {
        $label = $this->exportScopeLabel($courseId, $parallelId);

        $slug = \Illuminate\Support\Str::slug($label);

        return $slug !== '' ? $slug : 'general';
    }
}
