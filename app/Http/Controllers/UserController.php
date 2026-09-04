<?php

namespace App\Http\Controllers;

use App\Models\Course;
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

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'rol' => ['required', 'in:padre,madre,tutor,profesor,admin,estudiante'],
            'main_course_id' => ['nullable', 'exists:courses,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'rol' => $request->rol,
            'telefono' => $request->telefono,
            'estado' => 'activo',
        ];

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('users', 'public');
        }

        $data['main_course_id'] = $request->rol === 'profesor' && $request->filled('main_course_id')
            ? $request->main_course_id
            : null;

        User::create($data);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $user)
    {
        $courses = Course::all();

        return view('users.edit', compact('user', 'courses'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'rol' => ['required', 'in:padre,madre,tutor,profesor,admin,estudiante'],
            'main_course_id' => ['nullable', 'exists:courses,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', Password::min(8)->letters()->mixedCase()->numbers()->symbols()],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'rol' => $request->rol,
            'telefono' => $request->telefono,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $data['main_course_id'] = $request->rol === 'profesor' && $request->filled('main_course_id')
            ? $request->main_course_id
            : null;

        if ($request->hasFile('foto')) {
            if ($user->foto_path) {
                Storage::disk('public')->delete($user->foto_path);
            }

            $data['foto_path'] = $request->file('foto')->store('users', 'public');
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente.');
    }

    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (! $user->trashed()) {
            return back()->with('error', 'El usuario ya está activo.');
        }

        $user->restore();

        return redirect()->route('users.index')->with('success', 'Usuario restaurado correctamente.');
    }

    public function forceDestroy($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        if ($user->foto_path) {
            Storage::disk('public')->delete($user->foto_path);
        }

        $user->forceDelete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado permanentemente.');
    }

    public function toggleStatus(User $user)
    {
        $user->estado = ($user->estado == 'activo') ? 'inactivo' : 'activo';
        $user->save();

        return back()->with('success', 'Estado del usuario actualizado correctamente.');
    }
}
