<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\StudentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API de la app móvil (v1). Contrato: docs/api-app-v1.md.
 * Solo expone los campos que la app necesita; nunca el modelo completo.
 */
class AppController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ]]);
    }

    public function children(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isResponsibleRole()) {
            return $this->onlyFamilies();
        }

        $children = Student::visibleToFamily($user)
            ->with(['course', 'parallel', 'profesor', 'grades.subject', 'attendances'])
            ->orderBy('name')
            ->get()
            ->map(function (Student $student) {
                return StudentReport::listItem($student);
            });

        return response()->json(['data' => $children->values()]);
    }

    public function summary(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();
        if (! $user->isResponsibleRole()) {
            return $this->onlyFamilies();
        }
        if (! $user->canViewStudent($student)) {
            return response()->json(['message' => 'No tienes permiso para ver este estudiante.'], 403);
        }

        $student->load(['course', 'parallel', 'profesor', 'grades.subject', 'attendances']);
        return response()->json(['data' => StudentReport::identity($student) + ['report' => StudentReport::forApp($student)]]);
    }

    private function onlyFamilies(): JsonResponse
    {
        return response()->json(['message' => 'Esta sección es solo para familias.'], 403);
    }
}
