<?php

namespace App\Http\Controllers;

use App\Exports\StudentsExport;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'student_name' => ['nullable', 'string', 'max:255'],
            'course_id' => ['nullable', 'integer', 'exists:courses,id'],
            'parallel_id' => ['nullable', 'integer', 'exists:parallels,id'],
        ]);

        $rawStudentName = $filters['student_name'] ?? null;
        $studentName = $rawStudentName === null
            ? null
            : preg_replace('/\s+/', ' ', trim($rawStudentName));
        $hasNameFilter = $rawStudentName !== null;
        $hasOtherFilters = ! empty($filters['course_id']) || ! empty($filters['parallel_id']);
        $user = Auth::user();

        if ($hasNameFilter && $studentName === '' && ! $hasOtherFilters) {
            $students = collect();
        } else {
            $students = Student::with(['course', 'parallel', 'parent', 'teacher'])
                ->when($user->isProfesor(), function ($query) use ($user) {
                    $query->where('teacher_user_id', $user->id);
                })
                ->when($studentName, function ($query, $studentName) {
                    $query->where('name', 'like', '%' . $studentName . '%');
                })
                ->when($filters['course_id'] ?? null, function ($query, $courseId) {
                    $query->where('course_id', $courseId);
                })
                ->when($filters['parallel_id'] ?? null, function ($query, $parallelId) {
                    $query->where('parallel_id', $parallelId);
                })
                ->get();
        }

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

        $studentsByCourseAndParallel = $students->groupBy([
            function (Student $student) {
                return $student->course?->name ?? 'Unassigned course';
            },
            function (Student $student) {
                return $student->parallel?->name ?? 'Unassigned parallel';
            },
        ]);

        $courses = \App\Models\Course::orderBy('name')->get();
        $parallels = Parallel::with('course')->orderBy('name')->get();

        return view('students.index', compact(
            'students',
            'studentsByCourseAndParallel',
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
            'name' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'age' => 'required|integer|min:0|max:120',
            'gender' => 'required|in:Masculino,Femenino',
            'level' => 'required|in:básico,intermedio,avanzado',
            'registration_date' => 'required|date|before_or_equal:today',
            'course_id' => ['nullable', 'integer', 'min:0', 'exists:courses,id'],
            'parallel_id' => ['required', 'integer', 'min:0', 'exists:parallels,id'],
            'parent_user_id' => ['nullable', 'integer', 'min:0', Rule::exists('users', 'id')->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('role', ['padre', 'madre', 'tutor'])
                        ->orWhereIn('rol', ['padre', 'madre', 'tutor']);
                });

                $query->whereIn('status', ['active', 'activo']);
            })],
            'secondary_parent_user_id' => ['nullable', 'integer', 'min:0', 'different:parent_user_id', Rule::exists('users', 'id')->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('role', ['padre', 'madre', 'tutor'])
                        ->orWhereIn('rol', ['padre', 'madre', 'tutor']);
                });

                $query->whereIn('status', ['active', 'activo']);
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
            'name' => 'required|string|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/',
            'age' => 'required|integer|min:0|max:120',
            'gender' => 'required|in:Masculino,Femenino',
            'level' => 'required|in:básico,intermedio,avanzado',
            'registration_date' => 'sometimes|nullable|date|before_or_equal:today',
            'status' => 'required|in:activo,inactivo',
            'parallel_id' => ['required', 'integer', 'min:0', 'exists:parallels,id'],
            $parentForeignKey => ['nullable', 'integer', 'min:0', Rule::exists('users', 'id')->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('role', ['padre', 'madre', 'tutor'])
                        ->orWhereIn('rol', ['padre', 'madre', 'tutor']);
                });

                $query->whereIn('status', ['active', 'activo']);
            })],
            $teacherForeignKey => ['nullable', 'integer', 'min:0', Rule::exists('users', 'id')->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereIn('role', ['profesor', 'teacher'])
                        ->orWhereIn('rol', ['profesor', 'teacher']);
                });

                $query->whereIn('status', ['active', 'activo']);
            })],
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];

        $data = $request->validate($rules, $this->studentValidationMessages());
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
        return match ($user->rol) {
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
    public function exportExcelAll()
    {
        return Excel::download(new StudentsExport, 'reporte_estudiantes_' . date('Ymd_His') . '.xlsx');
    }

    // Wrapper for explicit all-students PDF download route
    public function exportPdfAll()
    {
        $students = Student::with(['padre', 'profesor', 'course', 'parallel'])->get();
        $pdf = Pdf::loadView('reports.students_pdf', compact('students'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('reporte_estudiantes_' . date('Ymd_His') . '.pdf');
    }
}
