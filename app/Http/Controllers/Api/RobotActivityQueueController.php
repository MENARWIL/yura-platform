<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\RobotActivity;
use App\Models\RobotActivityAttempt;
use App\Models\RobotDevice;
use App\Support\RobotAccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RobotActivityQueueController extends Controller
{
    /** Fecha del robot (ISO 8601, p. ej. 2026-11-23T05:10:59Z) en la zona horaria de la plataforma. */
    private function localTime(string $value): Carbon
    {
        return Carbon::parse($value)->setTimezone(config('app.timezone'));
    }

    /** Prueba de conexión: quién es el robot, su materia y el estado de su llave. */
    public function me(Request $request): JsonResponse
    {
        $device = RobotAccess::device($request);
        if (! $device && ! RobotAccess::isLegacy($request)) {
            return response()->json(['message' => 'Solo Yura puede usar esta ruta.'], 403);
        }

        if (! $device) {
            return response()->json(['name' => $request->user()->name, 'legacy' => true, 'contract' => '1.3']);
        }

        return response()->json([
            'name' => $device->name,
            'subject' => ['id' => $device->subject_id, 'name' => $device->subject?->name],
            'status' => $device->status,
            'abilities' => $request->user()->currentAccessToken()?->abilities ?? [],
            'last_used_at' => $device->last_used_at?->toISOString(),
            'contract' => '1.3',
        ]);
    }

    public function status(Request $request, string $activityId, string $attemptId): JsonResponse
    {
        if (! RobotAccess::allows($request, RobotDevice::ABILITY_RECEIVE)) {
            return response()->json(['message' => 'Solo Yura puede consultar actividades.'], 403);
        }

        $activity = RobotActivity::query()->findOrFail($activityId);
        $device = RobotAccess::device($request);
        if ($device && (int) $activity->subject_id !== (int) $device->subject_id) {
            return response()->json(['message' => 'Esta actividad no es de la materia de este robot.'], 403);
        }
        $attempt = RobotActivityAttempt::query()
            ->where('robot_activity_id', $activity->id)
            ->where('attempt_id', $attemptId)
            ->first();

        $status = match (true) {
            ! $attempt || (string) $activity->current_attempt_id !== $attemptId => 'superseded',
            $activity->status === 'cancelled' || $attempt->status === 'cancelled' => 'cancelled',
            default => $activity->status,
        };

        return response()->json([
            'activity_id' => $activity->id,
            'attempt_id' => $attemptId,
            'status' => $status,
        ]);
    }

    /**
     * Contrato v1.2 (transición): GET que reclama. Solo lo usa el usuario antiguo con rol «robot»;
     * un robot registrado reclama con POST /v1/robot/claims.
     */
    public function next(Request $request)
    {
        if (! RobotAccess::isLegacy($request)) {
            return response()->json(['message' => 'Solo Yura puede reclamar actividades.'], 403);
        }

        return $this->claimNext($request);
    }

    /** Contrato v1.3: POST que reclama la siguiente actividad de la materia del robot. */
    public function claim(Request $request)
    {
        if (! RobotAccess::allows($request, RobotDevice::ABILITY_RECEIVE)) {
            return response()->json(['message' => 'Solo Yura puede reclamar actividades.'], 403);
        }

        $response = $this->claimNext($request);

        return $response->getStatusCode() === 200 ? $response->setStatusCode(201) : $response;
    }

    private function claimNext(Request $request)
    {
        $device = RobotAccess::device($request);

        $activity = DB::transaction(function () use ($device) {
            $activity = RobotActivity::query()
                ->whereIn('status', ['pending', 'in_progress'])
                ->when($device, fn ($query) => $query->where('subject_id', $device->subject_id))
                ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $activity) {
                return null;
            }

            if ($activity->status === 'pending' || ! $activity->current_attempt_id) {
                $activity->attempt_number++;
                $activity->current_attempt_id = (string) Str::uuid();
                $activity->status = 'in_progress';
                $activity->started_at = now();
                $activity->save();
                RobotActivityAttempt::create([
                    'robot_activity_id' => $activity->id,
                    'robot_device_id' => $device?->id,
                    'attempt_number' => $activity->attempt_number,
                    'attempt_id' => $activity->current_attempt_id,
                    'status' => 'in_progress',
                    'started_at' => $activity->started_at,
                ]);
            }

            return $activity;
        });

        if (! $activity) {
            return response()->noContent();
        }

        $payload = $activity->payload;
        $payload['activity']['created_at'] = $activity->created_at->toISOString();
        $payload['activity']['attempt_id'] = $activity->current_attempt_id;
        $payload['activity']['attempt_number'] = $activity->attempt_number;

        return response()->json($payload);
    }

    public function results(Request $request, string $activityId): JsonResponse
    {
        if (! RobotAccess::allows($request, RobotDevice::ABILITY_SEND)) {
            return response()->json(['message' => 'Solo Yura puede enviar resultados.'], 403);
        }

        $activity = RobotActivity::findOrFail($activityId);

        // Un robot registrado solo entrega notas de su materia y de actividades que él mismo reclamó.
        $device = RobotAccess::device($request);
        if ($device) {
            $claimedBy = RobotActivityAttempt::query()
                ->where('robot_activity_id', $activity->id)
                ->where('attempt_id', $activity->current_attempt_id)
                ->value('robot_device_id');
            if ((int) $activity->subject_id !== (int) $device->subject_id
                || ($claimedBy !== null && (int) $claimedBy !== (int) $device->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta actividad no fue reclamada por este robot.',
                ], 403);
            }
        }

        if ($activity->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'La actividad fue cancelada y no puede registrar resultados.',
            ], 409);
        }
        $data = $this->validateResults($request, $activity);
        $fingerprint = hash('sha256', json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ));

        return DB::transaction(function () use ($activityId, $data, $fingerprint) {
            $lockedActivity = RobotActivity::query()->lockForUpdate()->findOrFail($activityId);

            if ($lockedActivity->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'La actividad fue cancelada y no puede registrar resultados.',
                ], 409);
            }

            if (! $lockedActivity->current_attempt_id
                || ! hash_equals((string) $lockedActivity->current_attempt_id, $data['attempt_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El resultado pertenece a un intento anterior o no reclamado.',
                ], 409);
            }

            if (in_array($lockedActivity->status, ['completed', 'partial', 'failed'], true)) {
                if (hash_equals((string) $lockedActivity->result_hash, $fingerprint)) {
                    return response()->json([
                        'success' => true,
                        'duplicate' => true,
                        'activity_id' => $lockedActivity->id,
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'La actividad ya tiene un resumen diferente registrado.',
                ], 409);
            }

            $payload = $lockedActivity->payload;
            $groupsById = collect($payload['activity']['groups'])->keyBy('group_id');
            $scoredStudentIds = collect($data['group_results'])
                ->flatMap(fn (array $groupResult) => collect($groupsById->get($groupResult['group_id'])['members'])
                    ->pluck('student_id'))
                ->unique()
                ->values();
            $existingGrades = Grade::query()
                ->whereIn('student_id', $scoredStudentIds)
                ->where('subject_id', $lockedActivity->subject_id)
                ->where('type', 'robot')
                ->where('activity_number', $lockedActivity->activity_number)
                ->where('quarter', $lockedActivity->quarter)
                ->get();

            if ($existingGrades->contains(
                fn (Grade $grade) => (string) $grade->robot_activity_id !== (string) $lockedActivity->id
            )) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una o más casillas HACER ya tienen una nota registrada. La actividad no modificó las calificaciones.',
                ], 409);
            }

            foreach ($data['group_results'] as $groupResult) {
                $group = $groupsById->get($groupResult['group_id']);
                $score = round(
                    ((float) $groupResult['points_earned'] / (float) $groupResult['points_possible']) * 100,
                    2
                );

                foreach ($group['members'] as $member) {
                    $grade = Grade::firstOrNew([
                        'student_id' => $member['student_id'],
                        'subject_id' => $lockedActivity->subject_id,
                        'type' => 'robot',
                        'activity_number' => $lockedActivity->activity_number,
                        'quarter' => $lockedActivity->quarter,
                    ]);
                    $grade->fill([
                        'score' => $score,
                        'type' => 'robot',
                        'robot_activity_id' => $lockedActivity->id,
                        'activity_number' => $lockedActivity->activity_number,
                        'quarter' => $lockedActivity->quarter,
                        'observations' => sprintf(
                            'Yura: actividad grupal %s (%s).',
                            $lockedActivity->id,
                            $score
                        ),
                        'status' => 'completed',
                        'activity_category' => $payload['activity']['content']['category'],
                        'is_robot_activity' => true,
                    ])->save();
                }
            }

            $lockedActivity->status = $data['status'];
            $lockedActivity->result_payload = $data;
            $lockedActivity->result_hash = $fingerprint;
            $lockedActivity->started_at = $this->localTime($data['started_at']);
            $lockedActivity->completed_at = $this->localTime($data['finished_at']);
            $lockedActivity->save();
            RobotActivityAttempt::query()
                ->where('robot_activity_id', $lockedActivity->id)
                ->where('attempt_id', $lockedActivity->current_attempt_id)
                ->update([
                    'status' => $data['status'],
                    'result_payload' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    'result_hash' => $fingerprint,
                    'started_at' => $this->localTime($data['started_at'])->format('Y-m-d H:i:s'),
                    'completed_at' => $this->localTime($data['finished_at'])->format('Y-m-d H:i:s'),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'duplicate' => false,
                'activity_id' => $lockedActivity->id,
                'status' => $lockedActivity->status,
            ], 201);
        });
    }

    private function validateResults(Request $request, RobotActivity $activity): array
    {
        $data = Validator::make($request->all(), [
            'contract_version' => ['required', 'in:1.2'],
            'activity_id' => ['required', 'uuid', 'in:' . $activity->id],
            'attempt_id' => ['required', 'uuid'],
            'status' => ['required', 'in:completed,partial,failed'],
            'failure_reason' => ['required_if:status,failed', 'nullable', 'string', 'max:500'],
            'started_at' => ['required', 'date'],
            'finished_at' => ['required', 'date', 'after_or_equal:started_at'],
            'group_results' => ['present', 'array'],
            'group_results.*.group_id' => ['required', 'string', 'max:64'],
            'group_results.*.member_student_ids' => ['required', 'array', 'min:1'],
            'group_results.*.member_student_ids.*' => ['required', 'integer', 'distinct'],
            'group_results.*.respondent_student_ids' => ['required', 'array', 'min:1'],
            'group_results.*.respondent_student_ids.*' => ['required', 'integer', 'distinct'],
            'group_results.*.points_earned' => ['required', 'numeric', 'min:0'],
            'group_results.*.points_possible' => ['required', 'numeric', 'gt:0'],
            'group_results.*.score_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'group_results.*.responses' => ['required', 'array'],
            'group_results.*.responses.*.student_id' => ['required', 'integer'],
            'group_results.*.responses.*.question_id' => ['required', 'string', 'max:100'],
            'group_results.*.responses.*.correct' => ['required', 'boolean'],
            'group_results.*.responses.*.points_earned' => ['required', 'numeric', 'min:0'],
            'group_results.*.responses.*.points_possible' => ['required', 'numeric', 'gt:0'],
            'group_results.*.responses.*.answered_at' => ['required', 'date'],
        ])->validate();

        if ($data['status'] === 'failed' && count($data['group_results']) > 0) {
            throw ValidationException::withMessages([
                'group_results' => ['Una actividad fallida no puede incluir resultados ni generar notas.'],
            ]);
        }

        $groups = collect($activity->payload['activity']['groups'])->keyBy('group_id');
        $seenGroups = [];
        $hasIncompleteGroup = false;

        foreach ($data['group_results'] as $groupResult) {
            $groupId = $groupResult['group_id'];
            $group = $groups->get($groupId);
            if (! $group || isset($seenGroups[$groupId])) {
                throw ValidationException::withMessages([
                    'group_results' => ['El resultado incluye un grupo desconocido o duplicado.'],
                ]);
            }
            $seenGroups[$groupId] = true;

            $expectedMembers = collect($group['members'])->pluck('student_id')->sort()->values()->all();
            $reportedMembers = collect($groupResult['member_student_ids'])->sort()->values()->all();
            if ($expectedMembers !== $reportedMembers) {
                throw ValidationException::withMessages([
                    'group_results' => ['Los integrantes no coinciden con el grupo enviado a Yura.'],
                ]);
            }

            $respondents = $groupResult['respondent_student_ids'];
            if (array_diff($respondents, $expectedMembers)) {
                throw ValidationException::withMessages([
                    'group_results' => ['Un participante reportado no pertenece al grupo.'],
                ]);
            }

            $execution = $activity->payload['activity']['execution'];
            $scoring = $activity->payload['activity']['scoring'];
            $plannedRespondents = collect($group['respondent_student_ids'])->sort()->values()->all();
            $reportedRespondents = collect($respondents)->sort()->values()->all();
            if ($plannedRespondents !== $reportedRespondents) {
                throw ValidationException::withMessages([
                    'group_results' => ['Los participantes evaluados no coinciden con el sorteo guardado por Laravel.'],
                ]);
            }

            $expectedRespondents = count($plannedRespondents);
            if (count($respondents) !== $expectedRespondents) {
                throw ValidationException::withMessages([
                    'group_results' => ['La cantidad de participantes evaluados no coincide con la actividad.'],
                ]);
            }

            $plannedResponses = $expectedRespondents * (int) $execution['questions_per_respondent'];
            $pointsPerResponse = round((float) $scoring['max_score'] / $plannedResponses, 4);
            $responsePointsEarned = 0.0;
            $responsePointsPossible = 0.0;
            $questionIds = [];
            $responsesPerStudent = array_fill_keys($respondents, 0);
            $expectedPercentage = round(
                ((float) $groupResult['points_earned'] / (float) $groupResult['points_possible']) * 100,
                2
            );
            if (abs($expectedPercentage - (float) $groupResult['score_percentage']) > 0.01) {
                throw ValidationException::withMessages([
                    'group_results' => ['El porcentaje no coincide con los puntos obtenidos.'],
                ]);
            }

            foreach ($groupResult['responses'] as $response) {
                if (! in_array($response['student_id'], $respondents, true)) {
                    throw ValidationException::withMessages([
                        'group_results' => ['Una respuesta pertenece a un alumno no seleccionado.'],
                    ]);
                }
                $responsesPerStudent[$response['student_id']]++;
                if ($responsesPerStudent[$response['student_id']] > (int) $execution['questions_per_respondent']) {
                    throw ValidationException::withMessages([
                        'group_results' => ['Un alumno no puede responder más preguntas que las planificadas.'],
                    ]);
                }
                if (isset($questionIds[$response['question_id']])) {
                    throw ValidationException::withMessages([
                        'group_results' => ['El resultado contiene una pregunta duplicada.'],
                    ]);
                }
                $questionIds[$response['question_id']] = true;

                if (abs((float) $response['points_possible'] - $pointsPerResponse) > 0.01) {
                    throw ValidationException::withMessages([
                        'group_results' => ['El puntaje posible por respuesta no coincide con la regla de la actividad.'],
                    ]);
                }
                $expectedEarned = $response['correct'] ? (float) $response['points_possible'] : 0.0;
                if (abs((float) $response['points_earned'] - $expectedEarned) > 0.01) {
                    throw ValidationException::withMessages([
                        'group_results' => ['El puntaje de una respuesta no coincide con su evaluación.'],
                    ]);
                }
                $responsePointsEarned += (float) $response['points_earned'];
                $responsePointsPossible += (float) $response['points_possible'];
            }

            if (
                abs($responsePointsEarned - (float) $groupResult['points_earned']) > 0.01
                || abs($responsePointsPossible - (float) $groupResult['points_possible']) > 0.01
            ) {
                throw ValidationException::withMessages([
                    'group_results' => ['Los totales del grupo no coinciden con el detalle de respuestas.'],
                ]);
            }

            if (
                count($groupResult['responses']) > $plannedResponses
            ) {
                throw ValidationException::withMessages([
                    'group_results' => ['Un grupo no puede reportar más respuestas que las planificadas.'],
                ]);
            }

            $groupIsComplete = count($groupResult['responses']) === $plannedResponses
                && collect($responsesPerStudent)->every(
                    fn (int $responseCount) => $responseCount === (int) $execution['questions_per_respondent']
                );
            if (! $groupIsComplete) {
                $hasIncompleteGroup = true;
            }

            if (
                $data['status'] === 'completed'
                && ! $groupIsComplete
            ) {
                throw ValidationException::withMessages([
                    'group_results' => ['Un grupo completo debe incluir todas las respuestas planificadas para cada participante.'],
                ]);
            }
        }

        if ($data['status'] === 'completed' && count($seenGroups) !== $groups->count()) {
            throw ValidationException::withMessages([
                'group_results' => ['Una actividad completa debe incluir el resultado de cada grupo.'],
            ]);
        }

        if (
            $data['status'] === 'partial'
            && count($seenGroups) === $groups->count()
            && ! $hasIncompleteGroup
        ) {
            throw ValidationException::withMessages([
                'status' => ['Una actividad con todos los grupos y respuestas completas debe reportarse como completed.'],
            ]);
        }

        return $data;
    }
}
