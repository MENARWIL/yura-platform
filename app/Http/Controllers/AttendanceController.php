<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\TeacherRecords;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /** Días que caben como columnas en el PDF; con más, solo va el resumen por estudiante. */
    private const PDF_MAX_DAY_COLUMNS = 31;

    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $subjects = Subject::where('active', true)->orderBy('sort_order')->orderBy('name')->get();
        $parallels = Parallel::with('course')->orderBy('course_id')->orderBy('name')->get();
        $selectedSubjectId = $request->integer('subject_id');
        $selectedParallelId = $request->integer('parallel_id');
        $selectedDate = $request->input('date') ?: now()->toDateString();

        $query = Student::with(['parent', 'teacher', 'parallel'])->active();

        if ($user->isProfesor()) {
            $query->taughtBy($user, $selectedSubjectId ?: null)
                ->when($selectedParallelId, function ($query) use ($selectedParallelId) {
                    return $query->where('parallel_id', $selectedParallelId);
                });

            $assignedSubjectIds = TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('active', true)
                ->distinct()
                ->pluck('subject_id');
            $teacherParallelIds = Student::where('teacher_user_id', $user->id)
                ->whereNotNull('parallel_id')
                ->distinct()
                ->pluck('parallel_id')
                ->merge(Student::assignedParallelIds($user));

            $subjects = $subjects->whereIn('id', $assignedSubjectIds)->values();
            $parallels = $parallels->whereIn('id', $teacherParallelIds)->values();
        } elseif ($selectedParallelId) {
            $query->where('parallel_id', $selectedParallelId);
        }

        $students = $query->orderedForList()->get();
        $studentIds = $students->pluck('id')->all();

        $attendanceForDate = Attendance::whereIn('student_id', $studentIds)
            ->when($selectedSubjectId, function ($query) use ($selectedSubjectId) {
                return $query->where('subject_id', $selectedSubjectId);
            })
            ->whereDate('date', $selectedDate)
            ->get()
            ->keyBy('student_id');

        $attendanceStatuses = Attendance::STATUSES;

        return view('attendance.index', compact(
            'students',
            'subjects',
            'parallels',
            'selectedSubjectId',
            'selectedParallelId',
            'selectedDate',
            'attendanceStatuses',
            'attendanceForDate'
        ));
    }

    public function exportPdf(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && !$user->isAcademic() && !$user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        // Rango de fechas que elige el profesor (un solo día con «date», por compatibilidad).
        $request->merge([
            'date_from' => $request->input('date_from', $request->input('date')) ?: now()->toDateString(),
            'date_to' => $request->input('date_to', $request->input('date')) ?: now()->toDateString(),
        ]);
        $range = $request->validate([
            'subject_id' => 'nullable|integer|exists:subjects,id',
            'parallel_id' => 'nullable|integer|exists:parallels,id',
            'date_from' => 'required|date|before_or_equal:today',
            'date_to' => 'required|date|after_or_equal:date_from|before_or_equal:today',
        ], $this->validationMessages() + [
            'date_from.before_or_equal' => 'La fecha «Desde» no puede ser futura.',
            'date_to.after_or_equal' => 'La fecha «Hasta» debe ser igual o posterior a «Desde».',
        ]);
        $dateFrom = Carbon::parse($range['date_from'])->startOfDay();
        $dateTo = Carbon::parse($range['date_to'])->startOfDay();
        if ($dateFrom->diffInDays($dateTo) > 366) {
            return back()->withErrors(['date_to' => 'El rango no puede superar un año.'])->withInput();
        }

        $selectedSubjectId = (int) ($range['subject_id'] ?? 0);
        $selectedParallelId = (int) ($range['parallel_id'] ?? 0);

        $subject = $selectedSubjectId ? Subject::find($selectedSubjectId) : null;
        $parallel = $selectedParallelId ? Parallel::with('course')->find($selectedParallelId) : null;

        $query = Student::with(['parent', 'teacher', 'parallel'])->active();

        if ($user->isProfesor()) {
            $query->taughtBy($user, $selectedSubjectId ?: null)
                ->when($selectedParallelId, function ($query) use ($selectedParallelId) {
                    return $query->where('parallel_id', $selectedParallelId);
                });
        } elseif ($selectedParallelId) {
            $query->where('parallel_id', $selectedParallelId);
        }

        $students = $query->orderedForList()->get();
        $studentIds = $students->pluck('id')->all();

        $records = Attendance::whereIn('student_id', $studentIds)
            ->when($selectedSubjectId, function ($query) use ($selectedSubjectId) {
                return $query->where('subject_id', $selectedSubjectId);
            })
            ->whereDate('date', '>=', $dateFrom->toDateString())
            ->whereDate('date', '<=', $dateTo->toDateString())
            ->orderBy('date')
            ->get();

        // Solo los días con algún registro (días de clase); [estudiante][Y-m-d] => estado.
        $days = $records->map(fn ($r) => $r->date->toDateString())->unique()->sort()->values();
        $grid = [];
        foreach ($records as $record) {
            $grid[$record->student_id][$record->date->toDateString()] = $record->status;
        }
        $totals = $students->mapWithKeys(function ($student) use ($records) {
            $own = $records->where('student_id', $student->id);
            $count = fn (string $status) => $own->filter(fn ($r) => Attendance::normalizeStatus($r->status) === $status)->count();

            return [$student->id => [
                'Presente' => $count('presente'),
                'Falta' => $count('falta'),
                'Atraso' => $count('atraso'),
                'Licencia' => $count('licencia'),
                'percent' => Attendance::presentPercent($own, 1),
            ]];
        });
        // Con muchos días la tabla no cabe en la hoja: se muestra solo el resumen por estudiante.
        $showDays = $days->isNotEmpty() && $days->count() <= self::PDF_MAX_DAY_COLUMNS;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.attendance_pdf', compact(
            'students',
            'subject',
            'parallel',
            'dateFrom',
            'dateTo',
            'days',
            'grid',
            'totals',
            'showDays'
        ));
        $pdf->setPaper('A4', $showDays && $days->count() > 6 ? 'landscape' : 'portrait');

        $name = $dateFrom->equalTo($dateTo)
            ? $dateFrom->format('Ymd')
            : $dateFrom->format('Ymd') . '_a_' . $dateTo->format('Ymd');

        return $pdf->download('asistencia_' . $name . '.pdf');
    }

    public function save(Request $request, TeacherRecords $records)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor() && ! $user->isAdmin() && ! $user->isAcademic()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'status' => ['required', 'string', Rule::in(Attendance::STATUSES)],
            'date' => 'required|date|before_or_equal:today',
        ], $this->validationMessages());

        $this->record($records, $user, (int) $data['subject_id'], $data['date'], [
            ['student_id' => (int) $data['student_id'], 'status' => $data['status']],
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Registra la lista completa del día en una sola petición (la pantalla parte con
     * todos en Presente y el profesor solo cambia a los que faltaron).
     */
    public function saveList(Request $request, TeacherRecords $records)
    {
        /** @var User $user */
        $user = Auth::user();

        // Mismas reglas que la app móvil (App\Services\TeacherRecords).
        $data = $request->validate(TeacherRecords::attendanceRules(), $this->validationMessages());
        $saved = $this->record($records, $user, (int) $data['subject_id'], $data['date'], $data['records']);

        return response()->json(['success' => true, 'saved' => $saved]);
    }

    private function record(TeacherRecords $records, User $user, int $subjectId, string $date, array $list): int
    {
        // Administración y académico pueden corregir; el profesor, solo en su asignación docente.
        if (! $user->isProfesor()) {
            foreach ($list as $item) {
                Attendance::updateOrCreate(
                    ['student_id' => $item['student_id'], 'subject_id' => $subjectId, 'date' => $date],
                    ['status' => $item['status']]
                );
            }

            return count($list);
        }

        try {
            return $records->saveAttendance($user, $subjectId, $date, $list);
        } catch (AuthorizationException) {
            abort(403, __('messages.unauthorized'));
        }
    }

    private function validationMessages(): array
    {
        return [
            'date.before_or_equal' => 'No se puede registrar asistencia en una fecha futura.',
            'status.in' => 'Estado de asistencia no válido.',
            'records.*.status.in' => 'Estado de asistencia no válido.',
        ];
    }

}
