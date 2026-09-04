<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TutorDashboardController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user || ! $user->isTutor()) {
            abort(403, 'Acceso denegado.');
        }

        $studentIds = $user->estudiantesComoFamiliar()->pluck('students.id');
        $attendance = Attendance::whereIn('student_id', $studentIds)
            ->latest('date')
            ->limit(10)
            ->get();

        $grades = Grade::whereIn('student_id', $studentIds)
            ->where('is_robot_activity', false)
            ->latest()
            ->limit(10)
            ->with('student', 'subject')
            ->get();

        $robotActivities = Grade::whereIn('student_id', $studentIds)
            ->where('is_robot_activity', true)
            ->latest()
            ->limit(10)
            ->with('student')
            ->get();

        return view('tutor.dashboard', compact('attendance', 'grades', 'robotActivities'));
    }
}
