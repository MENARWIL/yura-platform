<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
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

        $query = Student::with(['parent', 'teacher', 'parallel']);

        if ($user->isProfesor()) {
            $query->where('teacher_user_id', $user->id)
                ->when($selectedParallelId, function ($query) use ($selectedParallelId) {
                    return $query->where('parallel_id', $selectedParallelId);
                });

            $assignedSubjectIds = TeachingAssignment::where('teacher_user_id', $user->id)
                ->where('active', true)
                ->distinct()
                ->pluck('subject_id');
            $ownedParallelIds = Student::where('teacher_user_id', $user->id)
                ->whereNotNull('parallel_id')
                ->distinct()
                ->pluck('parallel_id');

            $subjects = $subjects->whereIn('id', $assignedSubjectIds)->values();
            $parallels = $parallels->whereIn('id', $ownedParallelIds)->values();
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

        $attendanceStatuses = ['Presente', 'Falta', 'Atraso'];

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

        $selectedSubjectId = $request->integer('subject_id');
        $selectedParallelId = $request->integer('parallel_id');
        $selectedDate = $request->input('date') ?: now()->toDateString();

        $subject = $selectedSubjectId ? Subject::find($selectedSubjectId) : null;
        $parallel = $selectedParallelId ? Parallel::with('course')->find($selectedParallelId) : null;

        $query = Student::with(['parent', 'teacher', 'parallel']);

        if ($user->isProfesor()) {
            $query->where('teacher_user_id', $user->id)
                ->when($selectedParallelId, function ($query) use ($selectedParallelId) {
                    return $query->where('parallel_id', $selectedParallelId);
                });
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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.attendance_pdf', compact(
            'students',
            'attendanceForDate',
            'subject',
            'parallel',
            'selectedDate'
        ));
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('asistencia_' . date('Ymd_His') . '.pdf');
    }

    public function save(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor() && ! $user->isAdmin() && ! $user->isAcademic()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'status' => 'required|string|in:Presente,Falta,Atraso',
            'date' => 'required|date',
        ]);

        $student = Student::findOrFail($data['student_id']);
        if ($user->isProfesor() && ! $this->hasTeachingAssignment($user, $student, (int) $data['subject_id'])) {
            abort(403, __('messages.unauthorized'));
        }

        try {
            Attendance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $data['subject_id'],
                    'date' => $data['date'],
                ],
                [
                    'status' => $data['status'],
                ]
            );

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function hasTeachingAssignment(User $user, Student $student, int $subjectId): bool
    {
        $assignmentQuery = TeachingAssignment::where('teacher_user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('active', true);

        if ((int) $student->teacher_user_id === (int) $user->id) {
            return $assignmentQuery->exists();
        }

        return $assignmentQuery->where('parallel_id', $student->parallel_id)->exists();
    }
}
