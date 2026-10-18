<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;
use App\Services\UserAdminException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Gestiones desde la app (solo rol admin, por el middleware de la ruta). Las reglas viven en AcademicYearService. */
class AppAdminYearController extends Controller
{
    public function __construct(private readonly AcademicYearService $years)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['year' => ['required', 'integer', 'between:2000,2100']]);

        return $this->respond(fn () => $this->years->create($request->user(), (int) $data['year']), 201);
    }

    public function current(Request $request, int $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);

        return $this->respond(fn () => $this->years->setCurrent($request->user(), $year));
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);
        $data = $request->validate(['next_year_id' => ['nullable', 'integer', 'exists:academic_years,id']]);
        $next = isset($data['next_year_id']) ? AcademicYear::find($data['next_year_id']) : null;

        return $this->respond(fn () => $this->years->close($request->user(), $year, $next));
    }

    public function reopen(Request $request, int $id): JsonResponse
    {
        $year = AcademicYear::findOrFail($id);

        return $this->respond(fn () => $this->years->reopen($request->user(), $year));
    }

    private function respond(callable $action, int $status = 200): JsonResponse
    {
        try {
            $year = $action();
        } catch (UserAdminException|\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => AppStaffController::yearItem($year)], $status);
    }
}
