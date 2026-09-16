<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['mainCourse', 'students.parallel'])
            ->where('id', '!=', Auth::id())
            ->get();
        $deletedUsers = User::onlyTrashed()
            ->with(['mainCourse', 'students.parallel'])
            ->where('id', '!=', Auth::id())
            ->get();

        return view('users.index', compact('users', 'deletedUsers'));
    }

    public function create()
    {
        $courses = Course::all();

        return view('users.create', compact('courses'));
    }

    public function createTeacher()
    {
        $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('teachers.create', compact('subjects'));
    }

    public function teacherIndex()
    {
        $teachers = User::whereIn('role', ['profesor', 'teacher'])
            ->whereIn('status', ['active', 'activo'])
            ->with('mainSubject')
            ->orderBy('name')
            ->get();

        return view('teachers.index', compact('teachers'));
    }

    public function storeTeacher(Request $request)
    {
        $request->merge($this->normalizeUserInput($request));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'main_subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'telefono' => ['required', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->userValidationMessages());

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'rol' => 'profesor',
            'telefono' => $validated['telefono'] ?? null,
            'estado' => 'activo',
            'main_subject_id' => $validated['main_subject_id'] ?? null,
        ];

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('users', 'public');
        }

        User::create($data);

        return redirect()->route('teachers.index')->with('success', __('messages.teacher_created'));
    }

    public function store(Request $request)
    {
        $request->merge($this->normalizeUserInput($request));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'rol' => ['required', 'in:padre,madre,tutor,profesor,admin,estudiante'],
            'main_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->userValidationMessages());

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'rol' => $validated['rol'],
            'telefono' => $validated['telefono'] ?? null,
            'estado' => 'activo',
        ];

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('users', 'public');
        }

        $data['main_course_id'] = $validated['rol'] === 'profesor' && isset($validated['main_course_id'])
            ? $validated['main_course_id']
            : null;

        User::create($data);

        return redirect()->route('users.index')->with('success', __('messages.user_created'));
    }

    public function edit(User $user)
    {
        if (request()->routeIs('teachers.edit')) {
            $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();

            return view('teachers.edit', compact('user', 'subjects'));
        }

        $courses = Course::all();

        return view('users.edit', compact('user', 'courses'));
    }

    public function update(Request $request, User $user)
    {
        $request->merge($this->normalizeUserInput($request));

        if ($request->routeIs('teachers.update')) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
                'main_subject_id' => ['required', 'integer', 'exists:subjects,id'],
                'telefono' => ['required', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
                'password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
                'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ], $this->userValidationMessages());

            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'telefono' => $validated['telefono'] ?? null,
                'main_subject_id' => $validated['main_subject_id'] ?? null,
            ];

            if (!empty($validated['password'])) {
                $data['password'] = Hash::make($validated['password']);
            }

            if ($request->hasFile('foto')) {
                if ($user->foto_path) {
                    Storage::disk('public')->delete($user->foto_path);
                }

                $data['foto_path'] = $request->file('foto')->store('users', 'public');
            }

            $user->update($data);

            return redirect()->route('teachers.index')->with('success', __('messages.teacher_updated'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\p{L}]+(?:[ \t]+[\p{L}]+)*$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'rol' => ['required', 'in:padre,madre,tutor,profesor,admin,estudiante'],
            'main_course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+() -]+$/'],
            'password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], $this->userValidationMessages());

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'rol' => $validated['rol'],
            'telefono' => $validated['telefono'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $data['main_course_id'] = $validated['rol'] === 'profesor' && isset($validated['main_course_id'])
            ? $validated['main_course_id']
            : null;

        if ($request->hasFile('foto')) {
            if ($user->foto_path) {
                Storage::disk('public')->delete($user->foto_path);
            }

            $data['foto_path'] = $request->file('foto')->store('users', 'public');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', __('messages.user_updated'));
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', __('messages.cannot_delete_self'));
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', __('messages.user_deleted'));
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (! $user->trashed()) {
            return back()->with('error', __('messages.user_already_active'));
        }

        $user->restore();

        return redirect()->route('users.index')->with('success', __('messages.user_restored'));
    }

    public function forceDestroy($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', __('messages.cannot_delete_self'));
        }

        if ($user->foto_path) {
            Storage::disk('public')->delete($user->foto_path);
        }

        $user->forceDelete();

        return redirect()->route('users.index')->with('success', __('messages.user_deleted_permanently'));
    }

    public function toggleStatus(User $user)
    {
        $user->estado = ($user->estado == 'activo') ? 'inactivo' : 'activo';
        $user->save();

        return back()->with('success', __('messages.user_status_updated'));
    }

    private function normalizeUserInput(Request $request): array
    {
        $name = preg_replace('/[ \t]+/u', ' ', trim((string) $request->input('name')));
        $email = strtolower(trim((string) $request->input('email')));
        $telefono = trim((string) $request->input('telefono'));

        return [
            'name' => $name,
            'email' => $email,
            'telefono' => $telefono === '' ? null : $telefono,
        ];
    }

    private function userValidationMessages(): array
    {
        return [
            'name.required' => __('messages.validation.required'),
            'name.regex' => __('messages.validation.name'),
            'email.required' => __('messages.validation.required'),
            'email.email' => __('messages.validation.email'),
            'email.unique' => __('messages.validation.unique'),
            'rol.required' => __('messages.validation.required'),
            'rol.in' => __('messages.validation.invalid'),
            'main_subject_id.required' => __('messages.validation.required'),
            'main_course_id.integer' => __('messages.validation.integer'),
            'telefono.regex' => __('messages.validation.phone'),
            'password.min' => __('messages.validation.password'),
            'password.letters' => __('messages.validation.password'),
            'password.mixed' => __('messages.validation.password'),
            'password.numbers' => __('messages.validation.password'),
            'password.symbols' => __('messages.validation.password'),
        ];
    }
}
