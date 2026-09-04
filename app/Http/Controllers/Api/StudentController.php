<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Parallel;
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
        $studentModel = new Student();
        $teacherForeignKey = $studentModel->getTeacherUserForeignKey();
        $parentForeignKey = $studentModel->getParentUserForeignKey();
        $studentUserForeignKey = $studentModel->getStudentUserForeignKey();

        $query = Student::with(['padre', 'profesor', 'parallel', 'course']);

        if ($user->isProfesor()) {
            $query->where($teacherForeignKey, $user->id);
        } elseif ($user->isEstudiante()) {
            $query->where($studentUserForeignKey, $user->id);
        } elseif (! $user->isAdmin()) {
            $query->where(function ($q) use ($user, $parentForeignKey) {
                $q->where($parentForeignKey, $user->id)
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
        $student = Student::with(['padre', 'profesor', 'parallel', 'course'])->find($id);

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
            'parallel_id' => 'required|exists:parallels,id',
            'usuario_id' => ['required', Rule::exists('users', 'id')->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('role', ['padre', 'madre', 'tutor'])
                        ->orWhereIn('rol', ['padre', 'madre', 'tutor']);
                });

                $query->where(function ($query) {
                    $query->where('status', 'activo')
                        ->orWhere('estado', 'activo');
                });
            })],
            'profesor_id' => ['nullable', Rule::exists('users', 'id')->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('role', ['profesor'])
                        ->orWhereIn('rol', ['profesor']);
                });

                $query->where(function ($query) {
                    $query->where('status', 'activo')
                        ->orWhere('estado', 'activo');
                });
            })],
            'puntaje' => 'nullable|numeric|min:0|max:100',
            'nota_escritura' => 'nullable|numeric|min:0|max:100',
            'nota_examen' => 'nullable|numeric|min:0|max:100',
            'asistencia' => 'nullable|numeric|min:0|max:100',
        ]);

        $parallel = Parallel::with('course')->findOrFail($data['parallel_id']);

        if ($parallel->students()->count() >= $parallel->max_students) {
            return response()->json([
                'success' => false,
                'message' => 'Este paralelo ha alcanzado su capacidad máxima de estudiantes.',
            ], 422);
        }

        $data['name'] = $data['nombre'];
        unset($data['nombre']);
        $data['course'] = $parallel->course->name;
        $data['course_id'] = $parallel->course_id;
        $data['parallel_id'] = $parallel->id;

        $student = Student::create($data);

        $familyUser = User::find($data['usuario_id']);
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

        return response()->json([
            'success' => true,
            'message' => 'Estudiante registrado con éxito',
            'data' => $student,
        ], 201);
    }

    private function resolveFamilyRelationType(User $user): string
    {
        return match ($user->rol) {
            'padre' => 'padre',
            'madre' => 'madre',
            default => 'tutor',
        };
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

        $teacherForeignKey = $student->getTeacherUserForeignKey();

        if ($user->isProfesor() && $student->getAttribute($teacherForeignKey) != $user->id) {
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
}
