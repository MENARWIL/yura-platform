<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $query = Student::with(['padre', 'profesor']);

        if ($user->isProfesor()) {
            $query->where('profesor_id', $user->id);
        } elseif ($user->isEstudiante()) {
            $query->where('user_id', $user->id);
        } elseif (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('usuario_id', $user->id)
                    ->orWhereHas('familiares', function ($family) use ($user) {
                        $family->where('user_id', $user->id);
                    });
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function show(Request $request, $id)
    {
        $student = Student::with(['padre', 'profesor'])->find($id);

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Estudiante no encontrado',
            ], 404);
        }

        if (! $request->user()->canViewStudent($student)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para ver este estudiante.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $student,
        ]);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isProfesor()) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para crear estudiantes.'], 403);
        }

        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'edad' => 'required|numeric',
            'genero' => 'required|in:Masculino,Femenino',
            'nivel' => 'required|in:básico,intermedio,avanzado',
            'fecha_registro' => 'required|date',
            'usuario_id' => ['required', Rule::exists('users', 'id')->whereIn('rol', ['padre', 'madre', 'tutor'])],
            'profesor_id' => ['nullable', Rule::exists('users', 'id')->where('rol', 'profesor')],
            'puntaje' => 'nullable|numeric|min:0|max:100',
            'nota_escritura' => 'nullable|numeric|min:0|max:100',
            'nota_examen' => 'nullable|numeric|min:0|max:100',
            'asistencia' => 'nullable|numeric|min:0|max:100',
        ]);

        $student = Student::create($data);
        $this->syncStudentAccount($student, $data['nombre']);

        return response()->json([
            'success' => true,
            'message' => 'Estudiante registrado con éxito',
            'data' => $student,
        ], 201);
    }

    public function updatePuntaje(Request $request, $id)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $student = Student::find($id);

        if (! $student) {
            return response()->json([
                'success' => false,
                'message' => 'Estudiante no encontrado',
            ], 404);
        }

        if ($user->isPadre() || $user->isMadre() || $user->isTutor() || $user->isEstudiante()) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para calificar a este estudiante.'], 403);
        }

        if ($user->isProfesor() && $student->profesor_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para calificar a este estudiante.'], 403);
        }

        $data = $request->validate([
            'puntaje' => 'nullable|numeric|min:0|max:100',
            'nota_escritura' => 'nullable|numeric|min:0|max:100',
            'nota_examen' => 'nullable|numeric|min:0|max:100',
            'asistencia' => 'nullable|numeric|min:0',
        ]);

        $student->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Métricas actualizadas correctamente',
            'data' => $student,
        ]);
    }

    private function syncStudentAccount(Student $student, string $name): void
    {
        if ($student->user_id) {
            $account = User::find($student->user_id);

            if ($account) {
                $account->name = $name;
                $account->rol = 'estudiante';
                $account->estado = 'activo';
                $account->save();

                return;
            }
        }

        $account = User::create([
            'name' => $name,
            'email' => $this->generateStudentEmail($name),
            'password' => Hash::make('password123'),
            'rol' => 'estudiante',
            'telefono' => null,
            'estado' => 'activo',
        ]);

        $student->user_id = $account->id;
        $student->save();
    }

    private function generateStudentEmail(string $name): string
    {
        $base = Str::slug($name, '-');
        $email = $base . '@yura.local';
        $suffix = 1;

        while (User::where('email', $email)->exists()) {
            $email = $base . $suffix . '@yura.local';
            $suffix++;
        }

        return $email;
    }
}
