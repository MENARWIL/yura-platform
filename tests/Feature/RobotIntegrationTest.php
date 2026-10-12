<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RobotIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_accepts_active_users(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.test',
            'password' => Hash::make('password123'),
            'role' => 'profesor',
            'status' => 'active',
        ]);

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->assertJsonPath('user.status', 'active');
    }

    public function test_robot_result_updates_the_exact_activity_and_subject(): void
    {
        $robotUser = User::factory()->create([
            'role' => 'robot',
            'status' => 'active',
        ]);
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $subject = Subject::create(['name' => 'Quechua Robot', 'code' => 'QROB']);
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 20]);
        $student = Student::create([
            'name' => 'Estudiante Robot',
            'course' => $course->name,
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'age' => 10,
            'gender' => 'Masculino',
            'level' => 'básico',
            'registration_date' => '2026-09-15',
        ]);

        $payload = [
            'activity_id' => 'robot-activity-001',
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 88,
            'activity_type' => 'colores',
        ];

        $this->actingAs($robotUser, 'sanctum')
            ->postJson('/api/v1/robot/activity', $payload)
            ->assertCreated();

        $this->actingAs($robotUser, 'sanctum')
            ->postJson('/api/v1/robot/activity', array_merge($payload, ['score' => 94]))
            ->assertOk();

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'robot_activity_id' => 'robot-activity-001',
            'score' => 94,
            'status' => 'completed',
            'is_robot_activity' => true,
        ]);
        $this->assertSame(1, Grade::where('robot_activity_id', 'robot-activity-001')->count());
    }

    public function test_web_robot_activity_creates_a_pending_activity_for_selected_students(): void
    {
        $teacher = User::factory()->create([
            'role' => 'profesor',
            'status' => 'active',
        ]);
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $subject = Subject::where('code', 'QUE')->firstOrFail();
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 20]);

        foreach (['Ana Robot', 'Luis Robot'] as $name) {
            Student::create([
                'name' => $name,
                'course' => $course->name,
                'course_id' => $course->id,
                'parallel_id' => $parallel->id,
                'teacher_user_id' => $teacher->id,
                'age' => 10,
                'gender' => 'Masculino',
                'level' => 'básico',
                'registration_date' => '2026-09-15',
            ]);
        }

        TeachingAssignment::create([
            'teacher_user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'parallel_id' => $parallel->id,
            'active' => true,
        ]);

        $this->actingAs($teacher)
            ->post('/robot-activities', [
                'parallel_id' => $parallel->id,
                'subject_id' => $subject->id,
                'quarter' => 1,
                'activity_number' => 1,
                'category' => 'nivel1_colores',
                'participant_ids' => Student::where('parallel_id', $parallel->id)->pluck('id')->all(),
                'group_size' => 3,
                'respondents_per_group' => 2,
                'questions_per_respondent' => 2,
            ])
            ->assertRedirect(route('robot-activities.index'));

        $this->assertDatabaseHas('robot_activities', [
            'parallel_id' => $parallel->id,
            'subject_id' => $subject->id,
            'quarter' => 1,
            'activity_number' => 1,
            'status' => 'pending',
        ]);
        $payload = \App\Models\RobotActivity::firstOrFail()->payload;
        $this->assertSame(
            2,
            collect($payload['activity']['groups'])->sum(fn ($group) => count($group['members']))
        );
        $this->assertSame(0, Grade::where('subject_id', $subject->id)
            ->where('is_robot_activity', true)
            ->count());
    }
}