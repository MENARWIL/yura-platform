<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Models\Parallel;
use App\Models\RobotActivity;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class RobotActivityController extends Controller
{
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, 'No tienes permiso para ver las actividades de YURA.');
        }

        $status = request()->query('status');
        $allowedStatuses = ['pending', 'in_progress', 'completed', 'partial', 'failed', 'cancelled'];
        if ($status !== null && ! in_array($status, $allowedStatuses, true)) {
            abort(404);
        }

        $activities = RobotActivity::query()
            ->with(['parallel.course', 'subject'])
            ->withCount('attempts')
            ->where('teacher_user_id', $user->id)
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(in_array((int) request()->query('per_page'), [5, 10, 15, 20], true) ? (int) request()->query('per_page') : 5)
            ->withQueryString();

        return view('robot.index', compact('activities', 'status', 'allowedStatuses'));
    }

    public function show(string $activityId)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, 'No tienes permiso para ver las actividades de YURA.');
        }

        $activity = RobotActivity::query()
            ->with(['parallel.course', 'subject', 'attempts'])
            ->where('teacher_user_id', $user->id)
            ->findOrFail($activityId);

        return view('robot.show', compact('activity'));
    }

    public function cancel(string $activityId)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $activity = DB::transaction(function () use ($activityId, $user) {
            $activity = RobotActivity::query()
                ->where('teacher_user_id', $user->id)
                ->lockForUpdate()
                ->findOrFail($activityId);

            if (! in_array($activity->status, ['pending', 'in_progress'], true)) {
                return null;
            }

            $activity->status = 'cancelled';
            $activity->completed_at = now();
            $activity->save();

            if ($activity->current_attempt_id) {
                $activity->attempts()
                    ->where('attempt_id', $activity->current_attempt_id)
                    ->where('status', 'in_progress')
                    ->update([
                        'status' => 'cancelled',
                        'completed_at' => $activity->completed_at,
                    ]);
            }

            return $activity;
        });

        if (! $activity) {
            return redirect()->route('robot-activities.show', $activityId)
                ->with('error', 'Solo se pueden cancelar actividades pendientes o que siguen en ejecución.');
        }

        return redirect()->route('robot-activities.show', $activityId)
            ->with('success', 'Actividad cancelada. No se guardarán notas parciales; Yura se detendrá al recibir la señal.');
    }

    public function retry(string $activityId)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $activity = DB::transaction(function () use ($activityId, $user) {
            $activity = RobotActivity::query()
                ->where('teacher_user_id', $user->id)
                ->lockForUpdate()
                ->findOrFail($activityId);

            if ($activity->status !== 'failed') {
                return null;
            }

            $groups = collect(data_get($activity->payload, 'activity.groups', []));
            $participantIds = $groups
                ->flatMap(fn (array $group) => collect($group['members'])->pluck('student_id'))
                ->unique()
                ->values();
            $hasGrade = Grade::query()
                ->whereIn('student_id', $participantIds)
                ->where('subject_id', $activity->subject_id)
                ->where('type', 'robot')
                ->where('quarter', $activity->quarter)
                ->where('activity_number', $activity->activity_number)
                ->where('status', 'completed')
                ->exists();

            if ($hasGrade) {
                return null;
            }

            $activity->status = 'pending';
            $activity->current_attempt_id = null;
            $activity->result_payload = null;
            $activity->result_hash = null;
            $activity->started_at = null;
            $activity->completed_at = null;
            $activity->save();

            return $activity;
        });

        if (! $activity) {
            return redirect()->route('robot-activities.show', $activityId)
                ->with('error', 'Solo se pueden reintentar actividades fallidas sin una nota HACER ya registrada.');
        }

        return redirect()->route('robot-activities.show', $activityId)
            ->with('success', 'Actividad reencolada con la misma configuración. Yura generará un nuevo identificador de intento.');
    }

    public function create()
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $assignments = TeachingAssignment::with('subject')
            ->where('teacher_user_id', $user->id)
            ->where('active', true)
            ->whereHas('subject', fn ($query) => $query->where('active', true))
            ->get();
        $parallelIds = $assignments->pluck('parallel_id')->unique();
        $parallels = Parallel::with(['course', 'students'])
            ->whereIn('id', $parallelIds)
            ->orderBy('course_id')
            ->orderBy('name')
            ->get();
        $subjectsByParallel = $assignments
            ->groupBy('parallel_id')
            ->map(fn ($parallelAssignments) => $parallelAssignments
                ->map(fn (TeachingAssignment $assignment) => [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                ])
                ->unique('id')
                ->sortBy('name')
                ->values())
            ->all();
        $participantsByParallel = $parallels->mapWithKeys(fn (Parallel $parallel) => [
            $parallel->id => $parallel->students->map(fn (Student $student) => [
                'id' => $student->id,
                'display_name' => $student->first_name ?: Str::before($student->name, ' '),
            ])->values(),
        ])->all();
        $selectedQuarter = request()->integer('quarter', 1);
        if (! in_array($selectedQuarter, [1, 2, 3], true)) {
            $selectedQuarter = 1;
        }
        $categories = [
            'Alimentos' => 'nivel1_alimentos',
            'Colores' => 'nivel1_colores',
            'Elementos de Casa' => 'nivel1_elementos_casa',
            'Elementos Naturales' => 'nivel1_elementos_naturales',
            'Familia' => 'nivel1_familia',
            'Números' => 'nivel1_numeros',
            'Objetos escolares' => 'nivel1_objetos_escolares',
            'Partes del cuerpo' => 'nivel1_partes_del_cuerpo',
            'Saludos' => 'nivel1_saludos',
            'Animales' => 'nivel2_animales',
        ];

        return view('robot.create', compact('parallels', 'subjectsByParallel', 'categories', 'participantsByParallel', 'selectedQuarter'));
    }

    public function store(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isProfesor()) {
            abort(403, __('messages.unauthorized'));
        }

        $data = $request->validate([
            'parallel_id' => 'required|integer|exists:parallels,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'quarter' => 'required|integer|in:1,2,3',
            'activity_number' => 'required|integer|between:1,5',
            'category' => 'required|string|in:nivel1_alimentos,nivel1_colores,nivel1_elementos_casa,nivel1_elementos_naturales,nivel1_familia,nivel1_numeros,nivel1_objetos_escolares,nivel1_partes_del_cuerpo,nivel1_saludos,nivel2_animales',
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'required|integer|distinct|exists:students,id',
            'group_size' => 'required|integer|between:1,20',
            'respondents_per_group' => 'required|integer|min:1|max:20',
            'questions_per_respondent' => 'required|integer|min:1|max:20',
        ]);

        if ($data['respondents_per_group'] > $data['group_size']) {
            throw ValidationException::withMessages([
                'respondents_per_group' => ['No puede seleccionar más participantes que integrantes por grupo.'],
            ]);
        }

        $parallel = Parallel::with(['course', 'students' => function ($query) {
            $query->orderBy('id');
        }])->findOrFail($data['parallel_id']);
        if (! TeachingAssignment::where('teacher_user_id', $user->id)
            ->where('subject_id', $data['subject_id'])
            ->where('parallel_id', $parallel->id)
            ->where('active', true)
            ->exists()) {
            abort(403, __('messages.unauthorized'));
        }

        $studentsById = $parallel->students->keyBy('id');
        $participantIds = collect($data['participant_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        if ($participantIds->diff($studentsById->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'participant_ids' => ['Todos los participantes deben pertenecer al paralelo seleccionado.'],
            ]);
        }

        $participants = $participantIds->map(fn ($id) => $studentsById->get($id));
        $participantsForGroups = $participants->shuffle();
        $groupCount = (int) ceil($participantsForGroups->count() / $data['group_size']);
        $baseGroupSize = intdiv($participantsForGroups->count(), $groupCount);
        $largerGroupCount = $participantsForGroups->count() % $groupCount;
        $groupSizes = collect(range(0, $groupCount - 1))
            ->map(fn ($index) => $baseGroupSize + ($index < $largerGroupCount ? 1 : 0));
        $offset = 0;
        $participantGroups = $groupSizes->map(function (int $size) use ($participantsForGroups, &$offset) {
            $members = $participantsForGroups->slice($offset, $size)->values();
            $offset += $size;

            return $members;
        });

        $robotActivityId = (string) Str::uuid();
        $groups = $participantGroups
            ->values()
            ->map(function ($members) use ($data) {
                $groupMembers = $members->map(fn (Student $student) => [
                    'student_id' => $student->id,
                    'display_name' => $student->first_name ?: Str::before($student->name, ' '),
                ])->values();
                $respondents = $groupMembers
                    ->shuffle()
                    ->take($data['respondents_per_group'])
                    ->pluck('student_id')
                    ->values()
                    ->all();

                return [
                    'group_id' => (string) Str::uuid(),
                    'members' => $groupMembers->all(),
                    'respondent_student_ids' => $respondents,
                ];
            })
            ->all();

        $payload = [
            'contract_version' => '1.2',
            'activity' => [
                'activity_id' => $robotActivityId,
                'course' => [
                    'id' => $parallel->course_id,
                    'name' => $parallel->course->name,
                ],
                'parallel' => [
                    'id' => $parallel->id,
                    'name' => $parallel->name,
                ],
                'subject' => [
                    'id' => (int) $data['subject_id'],
                    'name' => Subject::findOrFail($data['subject_id'])->name,
                ],
                'content' => [
                    'category' => $data['category'],
                ],
                'grading_target' => [
                    'quarter' => (int) $data['quarter'],
                    'activity_number' => (int) $data['activity_number'],
                    'type' => 'robot',
                ],
                'groups' => $groups,
                'execution' => [
                    'group_size' => (int) $data['group_size'],
                    'respondents_per_group' => (int) $data['respondents_per_group'],
                    'questions_per_respondent' => (int) $data['questions_per_respondent'],
                ],
                'scoring' => [
                    'max_score' => 100,
                    'correct_rule' => 'equal_weight_per_planned_response',
                    'incorrect_points' => 0,
                    'unanswered_points' => 0,
                    'normalization' => 'round(points_earned / points_possible * 100, 2)',
                ],
            ],
        ];

        DB::transaction(function () use ($participantIds, $data, $parallel, $user, $robotActivityId, $payload) {
            $lockedStudents = Student::query()
                ->whereIn('id', $participantIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($lockedStudents->count() !== $participantIds->count()
                || $lockedStudents->contains(fn (Student $student) => (int) $student->parallel_id !== (int) $parallel->id)) {
                throw ValidationException::withMessages([
                    'participant_ids' => ['Los participantes deben seguir perteneciendo al paralelo seleccionado.'],
                ]);
            }

            $hasExistingGrade = Grade::query()
                ->whereIn('student_id', $participantIds)
                ->where('subject_id', $data['subject_id'])
                ->where('type', 'robot')
                ->where('quarter', $data['quarter'])
                ->where('activity_number', $data['activity_number'])
                ->where('status', 'completed')
                ->exists();
            if ($hasExistingGrade) {
                throw ValidationException::withMessages([
                    'participant_ids' => ['Uno o más participantes ya tienen una nota en esa casilla HACER. Elige otra casilla o participantes sin calificar.'],
                ]);
            }

            $activeActivities = RobotActivity::query()
                ->where('parallel_id', $parallel->id)
                ->where('subject_id', $data['subject_id'])
                ->where('quarter', $data['quarter'])
                ->where('activity_number', $data['activity_number'])
                ->whereIn('status', ['pending', 'in_progress'])
                ->get(['payload']);
            $activeParticipantIds = $activeActivities
                ->flatMap(fn (RobotActivity $activity) => collect(data_get($activity->payload, 'activity.groups', []))
                    ->flatMap(fn (array $group) => collect($group['members'])->pluck('student_id')))
                ->map(fn ($id) => (int) $id);

            if ($participantIds->intersect($activeParticipantIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'participant_ids' => ['Uno o más participantes ya tienen una actividad pendiente o en ejecución para esa casilla HACER.'],
                ]);
            }

            RobotActivity::create([
                'id' => $robotActivityId,
                'teacher_user_id' => $user->id,
                'parallel_id' => $parallel->id,
                'subject_id' => $data['subject_id'],
                'quarter' => $data['quarter'],
                'activity_number' => $data['activity_number'],
                'status' => 'pending',
                'payload' => $payload,
            ]);
        });

        return redirect()->route('robot-activities.index')
            ->with('success', 'Actividad guardada y en espera de Yura.');
    }
}
