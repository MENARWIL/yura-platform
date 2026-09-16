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
            'subject_id' => 'required|integer|exists:subjects,id',
            'score' => 'required|numeric|min:0|max:100',
            'activity_type' => 'required|string|max:50',
            'observations' => 'nullable|string|max:1000',
        ]);

        $grade = Grade::firstOrNew([
            'student_id' => $data['student_id'],
            'subject_id' => $data['subject_id'],
            'robot_activity_id' => $data['activity_id'],
        ]);

        $observations = $data['observations'] ?? sprintf(
            'Robot activity %s (%s) received.',
            $data['activity_id'],
            $data['activity_type']
        );

        $wasExisting = $grade->exists;
        $grade->fill([
            'score' => $data['score'],
            'type' => 'robot',
            'observations' => $observations,
            'status' => 'completed',
            'is_robot_activity' => true,
            'activity_category' => $data['activity_type'],
        ])->save();

        $statusCode = $wasExisting ? 200 : 201;
        $message = $wasExisting
            ? 'Resultado de YURA actualizado correctamente.'
            : 'Resultado de YURA registrado correctamente.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $grade,
        ], $statusCode);
    }
}
