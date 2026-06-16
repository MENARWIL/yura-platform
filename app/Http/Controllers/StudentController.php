<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
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

        $students = $query->get();
        $deletedStudents = (clone $query)->onlyTrashed()->get();

        return view('students.index', compact('students', 'deletedStudents'));
    }

    public function create()
    {
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isProfesor()) {
            abort(403, 'Solo administradores y profesores pueden registrar estudiantes.');
        }

        $padres = User::whereIn('rol', ['padre', 'madre', 'tutor'])
            ->where('estado', 'activo')
            ->get();
        $profesores = User::where('rol', 'profesor')->where('estado', 'activo')->get();

        return view('students.create', compact('padres', 'profesores'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin() && ! $user->isProfesor()) {
            abort(403, 'Solo administradores y profesores pueden registrar estudiantes.');
        }

        $rules = [
            'nombre' => 'required|string|max:255',
            'edad' => 'required|numeric|min:1|max:100',
            'genero' => 'required|in:Masculino,Femenino',
            'nivel' => 'required|in:básico,intermedio,avanzado',
            'fecha_registro' => 'required|date',
            'puntaje' => 'nullable|numeric|min:0|max:100',
            'usuario_id' => ['required', Rule::exists('users', 'id')->whereIn('rol', ['padre', 'madre', 'tutor'])],
            'profesor_id' => ['nullable', Rule::exists('users', 'id')->where('rol', 'profesor')],
            'nota_escritura' => 'nullable|numeric|min:0|max:100',
            'nota_examen' => 'nullable|numeric|min:0|max:100',
            'asistencia' => 'nullable|numeric|min:0|max:100',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        $data = $request->validate($rules);

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('students', 'public');
        }

        $student = Student::create($data);
        $this->syncStudentAccount($student, $data['nombre']);

        return redirect()->route('students.index')->with('success', 'Estudiante creado con éxito.');
    }

    public function show(Student $student)
    {
        $this->authorizeAccess($student);

        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $this->authorizeAccess($student, true);
        $padres = User::whereIn('rol', ['padre', 'madre', 'tutor'])
            ->where('estado', 'activo')
            ->get();
        $profesores = User::where('rol', 'profesor')->where('estado', 'activo')->get();

        return view('students.edit', compact('student', 'padres', 'profesores'));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorizeAccess($student, true);

        $rules = [
            'nombre' => 'required|string|max:255',
            'edad' => 'required|numeric|min:1|max:100',
            'genero' => 'required|in:Masculino,Femenino',
            'nivel' => 'required|in:básico,intermedio,avanzado',
            'fecha_registro' => 'required|date',
            'estado' => 'required|in:activo,inactivo',
            'puntaje' => 'nullable|numeric|min:0|max:100',
            'usuario_id' => ['required', Rule::exists('users', 'id')->whereIn('rol', ['padre', 'madre', 'tutor'])],
            'profesor_id' => ['nullable', Rule::exists('users', 'id')->where('rol', 'profesor')],
            'nota_escritura' => 'nullable|numeric|min:0|max:100',
            'nota_examen' => 'nullable|numeric|min:0|max:100',
            'asistencia' => 'nullable|numeric|min:0|max:100',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        $data = $request->validate($rules);

        if ($request->hasFile('foto')) {
            if ($student->foto_path) {
                Storage::disk('public')->delete($student->foto_path);
            }

            $data['foto_path'] = $request->file('foto')->store('students', 'public');
        }

        $student->update($data);
        $this->syncStudentAccount($student, $data['nombre']);

        return redirect()->route('students.index')->with('success', 'Estudiante actualizado con éxito.');
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

        if ($user->isProfesor() && $student->profesor_id == $user->id) {
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

    private function authorizeAccess(Student $student, bool $manage = false)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        if ($user->isProfesor() && $student->profesor_id == $user->id) {
            return;
        }

        if ($user->isEstudiante() && $student->user_id == $user->id) {
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

    public function exportExcel()
    {
        return Excel::download(new StudentsExport, 'reporte_estudiantes_' . date('Ymd_His') . '.xlsx');
    }

    public function exportPdf()
    {
        $students = Student::with(['padre', 'profesor'])->get();
        $pdf = Pdf::loadView('reports.students_pdf', compact('students'));

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('reporte_estudiantes_' . date('Ymd_His') . '.pdf');
    }
}
