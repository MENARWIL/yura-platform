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

    public function test_web_robot_activity_creates_one_pending_record_per_student(): void
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
                'category' => 'Colores',
                'description' => 'Colores de prueba',
            ])
            ->assertRedirect(route('grades.index'));

        $grades = Grade::where('subject_id', $subject->id)
            ->where('is_robot_activity', true)
            ->get();

        $this->assertCount(2, $grades);
        $this->assertCount(1, $grades->pluck('robot_activity_id')->unique());
        $this->assertTrue($grades->every(fn (Grade $grade) => $grade->status === 'pending'));
    }
}