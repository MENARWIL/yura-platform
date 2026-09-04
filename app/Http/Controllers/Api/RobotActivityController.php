<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use Illuminate\Http\Request;

class RobotActivityController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'activity_id' => 'required|string|max:100',
            'student_id' => 'required|integer|exists:students,id',
            'course_id' => 'required|integer|exists:courses,id',
            'score' => 'required|numeric|min:0|max:100',
            'activity_type' => 'required|string|max:50',
            'observations' => 'nullable|string|max:1000',
        ]);

        $grade = Grade::where('student_id', $data['student_id'])
            ->where('subject_id', $data['course_id'])
            ->first();

        $observations = $data['observations'] ?? sprintf(
            'Robot activity %s (%s) received.',
            $data['activity_id'],
            $data['activity_type']
        );

        if ($grade) {
            $grade->update([
                'score' => $data['score'],
                'type' => $data['activity_type'],
                'observations' => $observations,
            ]);

            $statusCode = 200;
            $message = 'Calificación actualizada correctamente.';
        } else {
            $grade = Grade::create([
                'student_id' => $data['student_id'],
                'subject_id' => $data['course_id'],
                'score' => $data['score'],
                'type' => $data['activity_type'],
                'observations' => $observations,
            ]);

            $statusCode = 210;
            $message = 'Calificación creada correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $grade,
        ], $statusCode);
    }
}
