<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\RobotActivity;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotActivityQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_activity_is_delivered_and_group_score_fills_selected_hacer_slot(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();

        $this->actingAs($teacher)
            ->get(route('robot-activities.create', ['quarter' => 2]))
            ->assertOk()
            ->assertSee('name="category"', false)
            ->assertSee('name="activity_number"', false)
            ->assertSee('name="group_size"', false)
            ->assertSee('id="robotGroupBreakdown"', false)
            ->assertSee('max="20"', false)
            ->assertSee('nivel1_saludos')
            ->assertSee('nivel2_animales')
            ->assertDontSee('nivel1_herramientas')
            ->assertDontSee('name="topic"', false)
            ->assertDontSee('name="instructions"', false)
            ->assertDontSee('name="selection_mode"', false)
            ->assertDontSee('name="duration_seconds"', false)
            ->assertDontSee('name="max_score"', false)
            ->assertDontSee('name="prompt"', false)
            ->assertDontSee('name="expected_answer"', false);

        $this->actingAs($teacher)
            ->post('/robot-activities', $this->activityForm($parallel, $subject, $students))
            ->assertRedirect(route('robot-activities.index'));

        $this->actingAs($teacher)
            ->get(route('grades.index', [
                'subject_id' => $subject->id,
                'parallel_id' => $parallel->id,
                'quarter' => 2,
            ]))
            ->assertOk()
            ->assertDontSee('name="category"', false)
            ->assertDontSee('Lanzar Actividad')
            ->assertDontSee('name="activity_category"', false);

        $activity = RobotActivity::firstOrFail();
        $this->actingAs($teacher)
            ->get(route('robot-activities.index'))
            ->assertOk()
            ->assertSee('Actividad de Colores');
        $this->actingAs($teacher)
            ->get(route('robot-activities.show', $activity->id))
            ->assertOk()
            ->assertSee('Actividad de Colores')
            ->assertSee('Estudiante1')
            ->assertSee('Sorteado para responder')
            ->assertSee('Intentos registrados');

        $this->assertSame('pending', $activity->status);
        $this->assertSame(4, $activity->activity_number);
        $this->assertSame(2, $activity->quarter);
        $this->assertSame('nivel1_colores', data_get($activity->payload, 'activity.content.category'));
        $this->assertSame('1.2', data_get($activity->payload, 'contract_version'));
        $this->assertSame(100, data_get($activity->payload, 'activity.scoring.max_score'));
        $this->assertSame(
            'equal_weight_per_planned_response',
            data_get($activity->payload, 'activity.scoring.correct_rule')
        );
        $this->assertArrayNotHasKey('correct_points', data_get($activity->payload, 'activity.scoring'));
        $this->assertCount(2, data_get($activity->payload, 'activity.groups'));

        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $claim = $this->actingAs($robot, 'sanctum')
            ->getJson('/api/v1/robot/activities/next')
            ->assertOk()
            ->assertJsonPath('contract_version', '1.2')
            ->assertJsonPath('activity.attempt_number', 1)
            ->assertJsonPath('activity.activity_id', $activity->id)
            ->assertJsonPath('activity.grading_target.quarter', 2)
            ->assertJsonPath('activity.grading_target.activity_number', 4)
            ->assertJsonPath('activity.content.category', 'nivel1_colores')
            ->assertJsonPath('activity.execution.questions_per_respondent', 2)
            ->assertJsonMissingPath('data.activity');
        $attemptId = $claim->json('activity.attempt_id');
        $this->assertNotEmpty($attemptId);

        $groupResults = [];
        foreach (data_get($activity->payload, 'activity.groups') as $group) {
            $memberIds = array_column($group['members'], 'student_id');
            $respondentIds = $group['respondent_student_ids'];
            $groupResults[] = [
                'group_id' => $group['group_id'],
                'member_student_ids' => $memberIds,
                'respondent_student_ids' => $respondentIds,
                'points_earned' => 75,
                'points_possible' => 100,
                'score_percentage' => 75,
                'responses' => collect([
                    [true, 25],
                    [true, 25],
                    [false, 0],
                    [true, 25],
                ])->map(fn ($response, $index) => [
                    'student_id' => $respondentIds[intdiv($index, 2)],
                    'question_id' => "q-{$index}",
                    'correct' => $response[0],
                    'points_earned' => $response[1],
                    'points_possible' => 25,
                    'answered_at' => now()->toISOString(),
                ])->all(),
            ];
        }

        $result = [
            'contract_version' => '1.2',
            'activity_id' => $activity->id,
            'attempt_id' => $attemptId,
            'status' => 'completed',
            'started_at' => now()->subMinutes(5)->toISOString(),
            'finished_at' => now()->toISOString(),
            'group_results' => $groupResults,
        ];

        $invalidResult = $result;
        $invalidResult['group_results'][0]['responses'][0]['points_earned'] = 4;
        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", $invalidResult)
            ->assertUnprocessable();
        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());

        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", $result)
            ->assertCreated()
            ->assertJsonPath('duplicate', false);

        $this->assertSame(6, Grade::where('robot_activity_id', $activity->id)->count());
        foreach ($students as $student) {
            $this->assertDatabaseHas('grades', [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'robot_activity_id' => $activity->id,
                'activity_number' => 4,
                'quarter' => 2,
                'score' => 75,
                'status' => 'completed',
            ]);
        }

        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", $result)
            ->assertOk()
            ->assertJsonPath('duplicate', true);
        $this->assertSame(6, Grade::where('robot_activity_id', $activity->id)->count());

        $this->actingAs($teacher)
            ->from(route('robot-activities.create'))
            ->post('/robot-activities', $this->activityForm($parallel, $subject, $students))
            ->assertSessionHasErrors('participant_ids');
        $this->assertSame(1, RobotActivity::count());

        $result['group_results'][0]['points_earned'] = 50;
        $result['group_results'][0]['score_percentage'] = 50;
        $result['group_results'][0]['responses'][0]['correct'] = false;
        $result['group_results'][0]['responses'][0]['points_earned'] = 0;
        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", $result)
            ->assertConflict();
    }

    public function test_robot_service_cannot_deliver_results_for_an_unknown_group(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)
            ->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $attemptId = $this->claimAttempt($robot)['activity']['attempt_id'];

        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", [
                'contract_version' => '1.2',
                'activity_id' => $activity->id,
                'attempt_id' => $attemptId,
                'status' => 'completed',
                'started_at' => now()->subMinute()->toISOString(),
                'finished_at' => now()->toISOString(),
                'group_results' => [[
                    'group_id' => 'unknown-group',
                    'member_student_ids' => [$students[0]->id],
                    'respondent_student_ids' => [$students[0]->id],
                    'points_earned' => 1,
                    'points_possible' => 1,
                    'score_percentage' => 100,
                    'responses' => [[
                        'student_id' => $students[0]->id,
                        'question_id' => 'q-1',
                        'correct' => true,
                        'points_earned' => 1,
                        'points_possible' => 1,
                        'answered_at' => now()->toISOString(),
                    ]],
                ]],
            ])
            ->assertUnprocessable();
    }

    public function test_robot_cannot_replace_the_respondents_saved_by_laravel(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)
            ->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $group = data_get($activity->payload, 'activity.groups.0');
        $memberIds = array_column($group['members'], 'student_id');
        $plannedRespondents = $group['respondent_student_ids'];
        $replacementRespondent = array_values(array_diff($memberIds, $plannedRespondents))[0];
        $reportedRespondents = [$plannedRespondents[0], $replacementRespondent];
        $responses = [];
        foreach ($reportedRespondents as $studentId) {
            foreach (range(1, 2) as $questionNumber) {
                $responses[] = [
                    'student_id' => $studentId,
                    'question_id' => "q-{$studentId}-{$questionNumber}",
                    'correct' => true,
                    'points_earned' => 25,
                    'points_possible' => 25,
                    'answered_at' => now()->toISOString(),
                ];
            }
        }

        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $attemptId = $this->claimAttempt($robot)['activity']['attempt_id'];
        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", [
                'contract_version' => '1.2',
                'activity_id' => $activity->id,
                'attempt_id' => $attemptId,
                'status' => 'completed',
                'started_at' => now()->subMinute()->toISOString(),
                'finished_at' => now()->toISOString(),
                'group_results' => [[
                    'group_id' => $group['group_id'],
                    'member_student_ids' => $memberIds,
                    'respondent_student_ids' => $reportedRespondents,
                    'points_earned' => 100,
                    'points_possible' => 100,
                    'score_percentage' => 100,
                    'responses' => $responses,
                ]],
            ])
            ->assertUnprocessable();
        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());

        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());
    }

    public function test_teacher_cannot_view_another_teachers_activity_history(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)
            ->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $otherTeacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);

        $this->actingAs($otherTeacher)
            ->get(route('robot-activities.show', $activity->id))
            ->assertNotFound();
    }

    public function test_teacher_cannot_queue_another_activity_for_participants_with_a_pending_activity_in_same_slot(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $form = $this->activityForm($parallel, $subject, $students);

        $this->actingAs($teacher)->post('/robot-activities', $form)->assertRedirect(route('robot-activities.index'));
        $this->actingAs($teacher)
            ->from(route('robot-activities.create'))
            ->post('/robot-activities', $form)
            ->assertSessionHasErrors('participant_ids');

        $this->assertSame(1, RobotActivity::count());
    }

    public function test_large_group_activity_scores_every_group_member_and_requires_all_planned_responses(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom(60);
        $form = $this->activityForm($parallel, $subject, $students);
        $form['group_size'] = 20;
        $form['respondents_per_group'] = 10;
        $form['questions_per_respondent'] = 4;

        $this->actingAs($teacher)
            ->post('/robot-activities', $form)
            ->assertRedirect(route('robot-activities.index'));

        $activity = RobotActivity::firstOrFail();
        $groups = data_get($activity->payload, 'activity.groups');
        $this->assertCount(3, $groups);
        foreach ($groups as $group) {
            $this->assertCount(20, $group['members']);
        }
        $this->assertSame(20, data_get($activity->payload, 'activity.execution.group_size'));
        $this->assertSame(10, data_get($activity->payload, 'activity.execution.respondents_per_group'));
        $this->assertSame(4, data_get($activity->payload, 'activity.execution.questions_per_respondent'));
        $this->assertArrayNotHasKey('meta', $activity->payload['activity']);

        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $claim = $this->claimAttempt($robot);
        $attemptId = $claim['activity']['attempt_id'];
        $this->assertSame($activity->id, $claim['activity']['activity_id']);
        $this->assertSame(1, $claim['activity']['attempt_number']);

        $groupResults = [];
        foreach ($groups as $group) {
            $memberIds = array_column($group['members'], 'student_id');
            $respondents = $group['respondent_student_ids'];
            $responses = [];
            $earned = 0.0;
            $possible = 0.0;
            $responseNumber = 0;

            foreach ($respondents as $respondentId) {
                foreach (range(1, 4) as $questionNumber) {
                    $correct = $responseNumber < 30;
                    $pointsPossible = 2.5;
                    $pointsEarned = $correct ? $pointsPossible : 0.0;
                    $responses[] = [
                        'student_id' => $respondentId,
                        'question_id' => "{$group['group_id']}-{$respondentId}-{$questionNumber}",
                        'correct' => $correct,
                        'points_earned' => $pointsEarned,
                        'points_possible' => $pointsPossible,
                        'answered_at' => now()->toISOString(),
                    ];
                    $earned += $pointsEarned;
                    $possible += $pointsPossible;
                    $responseNumber++;
                }
            }

            $groupResults[] = [
                'group_id' => $group['group_id'],
                'member_student_ids' => $memberIds,
                'respondent_student_ids' => $respondents,
                'points_earned' => $earned,
                'points_possible' => $possible,
                'score_percentage' => round($earned / $possible * 100, 2),
                'responses' => $responses,
            ];
        }

        $result = [
            'contract_version' => '1.2',
            'activity_id' => $activity->id,
            'attempt_id' => $attemptId,
            'status' => 'completed',
            'started_at' => now()->subMinutes(5)->toISOString(),
            'finished_at' => now()->toISOString(),
            'group_results' => $groupResults,
        ];

        $incompleteResult = $result;
        array_pop($incompleteResult['group_results'][0]['responses']);
        $incompleteResult['group_results'][0]['points_possible'] = 97.5;
        $incompleteResult['group_results'][0]['score_percentage'] = round(
            $incompleteResult['group_results'][0]['points_earned'] / 97.5 * 100,
            2
        );
        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", $incompleteResult)
            ->assertUnprocessable();
        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());

        $mislabelledPartial = $result;
        $mislabelledPartial['status'] = 'partial';
        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", $mislabelledPartial)
            ->assertUnprocessable();

        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", $result)
            ->assertCreated()
            ->assertJsonPath('status', 'completed');

        $this->assertSame(60, Grade::where('robot_activity_id', $activity->id)->count());
        foreach ($students as $student) {
            $this->assertDatabaseHas('grades', [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'robot_activity_id' => $activity->id,
                'activity_number' => $activity->activity_number,
                'quarter' => $activity->quarter,
                'score' => 75,
                'status' => 'completed',
            ]);
        }

        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", $result)
            ->assertOk()
            ->assertJsonPath('duplicate', true);
        $this->assertSame(60, Grade::where('robot_activity_id', $activity->id)->count());
    }

    public function test_groups_are_balanced_and_respondent_limit_uses_actual_smallest_group(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom(23);
        $form = $this->activityForm($parallel, $subject, $students);
        $form['group_size'] = 20;
        $form['respondents_per_group'] = 10;

        $this->actingAs($teacher)
            ->post('/robot-activities', $form)
            ->assertRedirect(route('robot-activities.index'));

        $activity = RobotActivity::firstOrFail();
        $groupSizes = collect(data_get($activity->payload, 'activity.groups'))
            ->map(fn (array $group) => count($group['members']))
            ->sort()
            ->values()
            ->all();
        $this->assertSame([11, 12], $groupSizes);
    }

    public function test_group_sizes_are_shown_balanced_for_ten_students_and_maximum_four(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom(10);
        $form = $this->activityForm($parallel, $subject, $students);
        $form['group_size'] = 4;
        $form['respondents_per_group'] = 2;

        $this->actingAs($teacher)
            ->post('/robot-activities', $form)
            ->assertRedirect(route('robot-activities.index'));

        $activity = RobotActivity::firstOrFail();
        $groups = data_get($activity->payload, 'activity.groups');
        $groupSizes = collect($groups)
            ->map(fn (array $group) => count($group['members']))
            ->sort()
            ->values()
            ->all();
        $this->assertSame([3, 3, 4], $groupSizes);

        $this->actingAs($teacher)
            ->get(route('robot-activities.show', $activity->id))
            ->assertOk()
            ->assertSee('Grupo 1')
            ->assertSee('Sorteado para responder');
    }

    public function test_activity_uses_all_members_when_a_group_is_smaller_than_the_respondent_target(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom(22);
        $form = $this->activityForm($parallel, $subject, $students);
        $form['group_size'] = 20;
        $form['respondents_per_group'] = 12;

        $this->actingAs($teacher)
            ->post('/robot-activities', $form)
            ->assertRedirect(route('robot-activities.index'));

        $groups = data_get(RobotActivity::firstOrFail()->payload, 'activity.groups');
        $this->assertSame([11, 11], collect($groups)
            ->map(fn (array $group) => count($group['respondent_student_ids']))
            ->sort()
            ->values()
            ->all());
    }

    public function test_teacher_cannot_manually_overwrite_a_yura_grade_in_the_notebook(): void
    {
        [$teacher, , $subject, $students] = $this->makeClassroom();

        $this->actingAs($teacher)
            ->postJson('/grades/save', [
                'student_id' => $students[0]->id,
                'subject_id' => $subject->id,
                'type' => 'robot',
                'activity_number' => 1,
                'quarter' => 1,
                'score' => 90,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
    }

    public function test_robot_result_does_not_overwrite_a_grade_added_after_activity_was_queued(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $group = data_get($activity->payload, 'activity.groups.0');
        $memberIds = array_column($group['members'], 'student_id');
        $respondentIds = $group['respondent_student_ids'];
        $responses = [];
        foreach ($respondentIds as $respondentId) {
            foreach (range(1, 2) as $questionNumber) {
                $responses[] = [
                    'student_id' => $respondentId,
                    'question_id' => "q-{$respondentId}-{$questionNumber}",
                    'correct' => true,
                    'points_earned' => 25,
                    'points_possible' => 25,
                    'answered_at' => now()->toISOString(),
                ];
            }
        }
        array_pop($responses);

        Grade::create([
            'student_id' => $memberIds[0],
            'subject_id' => $subject->id,
            'score' => 80,
            'type' => 'robot',
            'status' => 'completed',
            'quarter' => $activity->quarter,
            'activity_number' => $activity->activity_number,
        ]);

        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $attemptId = $this->claimAttempt($robot)['activity']['attempt_id'];
        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", [
                'contract_version' => '1.2',
                'activity_id' => $activity->id,
                'attempt_id' => $attemptId,
                'status' => 'partial',
                'started_at' => now()->subMinute()->toISOString(),
                'finished_at' => now()->toISOString(),
                'group_results' => [[
                    'group_id' => $group['group_id'],
                    'member_student_ids' => $memberIds,
                    'respondent_student_ids' => $respondentIds,
                    'points_earned' => 75,
                    'points_possible' => 75,
                    'score_percentage' => 100,
                    'responses' => $responses,
                ]],
            ])
            ->assertConflict();

        $this->assertDatabaseHas('grades', [
            'student_id' => $memberIds[0],
            'robot_activity_id' => null,
            'score' => 80,
        ]);
        $this->assertSame('in_progress', $activity->fresh()->status);
        $this->assertSame(1, Grade::count());
    }

    public function test_teacher_can_cancel_pending_activity_and_cerebro_will_not_receive_it(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();

        $this->actingAs($teacher)
            ->post(route('robot-activities.cancel', $activity->id))
            ->assertRedirect(route('robot-activities.show', $activity->id));
        $this->assertSame('cancelled', $activity->fresh()->status);

        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $this->actingAs($robot, 'sanctum')
            ->getJson('/api/v1/robot/activities/next')
            ->assertNoContent();
        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", [
            'contract_version' => '1.2',
            'activity_id' => $activity->id,
            'attempt_id' => (string) \Illuminate\Support\Str::uuid(),
            'status' => 'failed',
            'failure_reason' => 'Late callback after cancellation.',
            'started_at' => now()->subMinute()->toISOString(),
            'finished_at' => now()->toISOString(),
            'group_results' => [],
        ])->assertConflict();
        $this->assertSame('cancelled', $activity->fresh()->status);
    }

    public function test_teacher_can_cancel_in_progress_activity_without_saving_partial_grades(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $attemptId = $this->claimAttempt($robot)['activity']['attempt_id'];

        $this->actingAs($robot, 'sanctum')
            ->getJson("/api/v1/robot/activities/{$activity->id}/attempts/{$attemptId}/status")
            ->assertOk()
            ->assertJsonPath('status', 'in_progress');

        $this->actingAs($teacher)
            ->post(route('robot-activities.cancel', $activity->id))
            ->assertRedirect(route('robot-activities.show', $activity->id))
            ->assertSessionHas('success');
        $this->assertSame('cancelled', $activity->fresh()->status);
        $this->assertDatabaseHas('robot_activity_attempts', [
            'robot_activity_id' => $activity->id,
            'attempt_id' => $attemptId,
            'status' => 'cancelled',
        ]);
        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());

        $this->actingAs($robot, 'sanctum')
            ->getJson("/api/v1/robot/activities/{$activity->id}/attempts/{$attemptId}/status")
            ->assertOk()
            ->assertJsonPath('activity_id', $activity->id)
            ->assertJsonPath('attempt_id', $attemptId)
            ->assertJsonPath('status', 'cancelled');

        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", [
                'contract_version' => '1.2',
                'activity_id' => $activity->id,
                'attempt_id' => $attemptId,
                'status' => 'failed',
                'failure_reason' => 'Late callback after cancellation.',
                'started_at' => now()->subMinute()->toISOString(),
                'finished_at' => now()->toISOString(),
                'group_results' => [],
            ])
            ->assertConflict();
        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());
    }

    public function test_activity_status_endpoint_rejects_non_robot_users_and_marks_old_attempt_superseded(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $attemptId = $this->claimAttempt($robot)['activity']['attempt_id'];
        $otherAttemptId = (string) \Illuminate\Support\Str::uuid();

        $this->actingAs($teacher)
            ->getJson("/api/v1/robot/activities/{$activity->id}/attempts/{$attemptId}/status")
            ->assertForbidden();

        $this->actingAs($robot, 'sanctum')
            ->getJson("/api/v1/robot/activities/{$activity->id}/attempts/{$otherAttemptId}/status")
            ->assertOk()
            ->assertJsonPath('status', 'superseded');
    }

    public function test_teacher_can_retry_failed_activity_without_losing_attempt_history_or_accepting_stale_results(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $firstAttemptId = $this->claimAttempt($robot)['activity']['attempt_id'];
        $failedResult = [
            'contract_version' => '1.2',
            'activity_id' => $activity->id,
            'attempt_id' => $firstAttemptId,
            'status' => 'failed',
            'failure_reason' => 'Temporary connection error.',
            'started_at' => now()->subMinute()->toISOString(),
            'finished_at' => now()->toISOString(),
            'group_results' => [],
        ];

        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", $failedResult)
            ->assertCreated()
            ->assertJsonPath('status', 'failed');

        $this->actingAs($teacher)
            ->post(route('robot-activities.retry', $activity->id))
            ->assertRedirect(route('robot-activities.show', $activity->id));
        $this->assertSame('pending', $activity->fresh()->status);
        $this->assertDatabaseHas('robot_activity_attempts', [
            'robot_activity_id' => $activity->id,
            'attempt_number' => 1,
            'attempt_id' => $firstAttemptId,
            'status' => 'failed',
        ]);

        $secondClaim = $this->claimAttempt($robot);
        $secondAttemptId = $secondClaim['activity']['attempt_id'];
        $this->assertSame($activity->id, $secondClaim['activity']['activity_id']);
        $this->assertSame(2, $secondClaim['activity']['attempt_number']);
        $this->assertNotSame($firstAttemptId, $secondAttemptId);

        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", $failedResult)
            ->assertConflict();

        $failedResult['attempt_id'] = $secondAttemptId;
        $this->postJson("/api/v1/robot/activities/{$activity->id}/results", $failedResult)
            ->assertCreated()
            ->assertJsonPath('status', 'failed');

        $this->actingAs($teacher)
            ->get(route('robot-activities.show', $activity->id))
            ->assertOk()
            ->assertSee('Intento 1')
            ->assertSee('Intento 2')
            ->assertSee('Reintentar actividad');

        $this->actingAs($teacher)
            ->post(route('robot-activities.retry', $activity->id))
            ->assertRedirect(route('robot-activities.show', $activity->id));
        $this->assertSame(2, $activity->attempts()->count());
    }

    public function test_failed_activity_accepts_an_empty_group_results_array_without_creating_grades(): void
    {
        [$teacher, $parallel, $subject, $students] = $this->makeClassroom();
        $this->actingAs($teacher)
            ->post('/robot-activities', $this->activityForm($parallel, $subject, $students));
        $activity = RobotActivity::firstOrFail();
        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $attemptId = $this->claimAttempt($robot)['activity']['attempt_id'];

        $this->actingAs($robot, 'sanctum')
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", [
                'contract_version' => '1.2',
                'activity_id' => $activity->id,
                'attempt_id' => $attemptId,
                'status' => 'failed',
                'failure_reason' => 'El cerebro no pudo ejecutar la actividad.',
                'started_at' => now()->subMinute()->toISOString(),
                'finished_at' => now()->toISOString(),
                'group_results' => [],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'failed');

        $this->assertSame('failed', $activity->fresh()->status);
        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());

        $this->actingAs($teacher)
            ->post('/robot-activities', $this->activityForm($parallel, $subject, $students))
            ->assertRedirect(route('robot-activities.index'));
        $this->assertSame(2, RobotActivity::count());
    }

    private function makeClassroom(int $studentCount = 6): array
    {
        $teacher = User::factory()->create([
            'role' => 'profesor',
            'status' => 'active',
        ]);
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $subject = Subject::where('code', 'QUE')->firstOrFail();
        $parallel = Parallel::create([
            'name' => 'A',
            'course_id' => $course->id,
            'max_students' => $studentCount,
        ]);

        $students = collect(range(1, $studentCount))->map(fn ($number) => Student::create([
            'name' => "Estudiante {$number}",
            'first_name' => "Estudiante{$number}",
            'course' => $course->name,
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'teacher_user_id' => $teacher->id,
            'age' => 10,
            'gender' => 'Femenino',
            'level' => 'básico',
            'registration_date' => '2026-10-01',
        ]))->all();

        TeachingAssignment::create([
            'teacher_user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'parallel_id' => $parallel->id,
            'active' => true,
        ]);

        return [$teacher, $parallel, $subject, $students];
    }

    private function activityForm(Parallel $parallel, Subject $subject, array $students): array
    {
        return [
            'parallel_id' => $parallel->id,
            'subject_id' => $subject->id,
            'quarter' => 2,
            'activity_number' => 4,
            'category' => 'nivel1_colores',
            'participant_ids' => array_map(fn (Student $student) => $student->id, $students),
            'group_size' => 3,
            'respondents_per_group' => 2,
            'questions_per_respondent' => 2,
        ];
    }

    private function claimAttempt(User $robot): array
    {
        return $this->actingAs($robot, 'sanctum')
            ->getJson('/api/v1/robot/activities/next')
            ->assertOk()
            ->json();
    }
}
