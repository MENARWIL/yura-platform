<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API de la app móvil v1 (docs/api-app-v1.md).
 */
class AppApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'active'], $extra));
    }

    private function student(string $name, ?User $parent = null, ?User $teacher = null): Student
    {
        $course = Course::firstOrCreate(['name' => '6° Primaria']);
        $parallel = Parallel::firstOrCreate(['name' => 'A', 'course_id' => $course->id], ['max_students' => 25]);

        return Student::create([
            'name' => $name,
            'course' => '6° Primaria',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $parent?->id,
            'teacher_user_id' => $teacher?->id,
            'edad' => 11,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'registration_date' => '2026-05-25',
            'puntaje' => 80,
        ]);
    }

    private function grade(Student $student, float $score, array $extra = []): void
    {
        Grade::create(array_merge([
            'student_id' => $student->id,
            'subject_id' => Subject::firstOrCreate(['name' => 'Quechua'], ['code' => 'QUE'])->id,
            'score' => $score,
            'type' => 'activity',
            'status' => 'completed',
            'is_robot_activity' => false,
            'quarter' => 1,
        ], $extra));
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/v1/app/children')->assertUnauthorized();
        $this->getJson('/api/v1/app/me')->assertUnauthorized();
    }

    public function test_parent_lists_only_own_children_with_summary_numbers(): void
    {
        $teacher = $this->user('profesor', ['name' => 'Prof. Vargas']);
        $parent = $this->user('padre');
        $other = $this->user('madre');
        $mine = $this->student('Ana Paz', $parent, $teacher);
        $this->student('Hijo Ajeno', $other);
        $this->grade($mine, 80);
        $this->grade($mine, 60);
        $this->grade($mine, 100, ['is_robot_activity' => true, 'type' => 'robot']);

        $response = $this->actingAs($parent, 'sanctum')->getJson('/api/v1/app/children')->assertOk();

        $this->assertSame(['Ana Paz'], collect($response->json('data'))->pluck('name')->all());
        $child = $response->json('data.0');
        $this->assertEquals(70, $child['average']);
        $this->assertSame(1, $child['robot_completed']);
        $this->assertSame('6° Primaria', $child['course']);
        $this->assertSame('Prof. Vargas', $child['teacher']);
    }

    public function test_children_response_does_not_leak_contact_data(): void
    {
        $parent = $this->user('padre', ['email' => 'secreto@school.bo', 'phone' => '77712345']);
        $this->student('Ana Paz', $parent, $this->user('profesor', ['email' => 'prof@school.bo']));

        $body = $this->actingAs($parent, 'sanctum')->getJson('/api/v1/app/children')->assertOk()->getContent();

        $this->assertStringNotContainsString('prof@school.bo', $body);
        $this->assertStringNotContainsString('77712345', $body);
        $this->assertStringNotContainsString('padre', $body);
    }

    public function test_summary_returns_trimesters_subjects_and_attendance(): void
    {
        $parent = $this->user('padre');
        $student = $this->student('Ana Paz', $parent);
        $this->grade($student, 90, ['quarter' => 1]);
        $this->grade($student, 70, ['quarter' => 2]);
        $this->grade($student, 50, ['quarter' => 1, 'status' => 'pending']); // no cuenta
        $subject = Subject::where('name', 'Quechua')->first();
        Attendance::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'date' => '2026-10-01', 'status' => 'presente']);
        Attendance::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'date' => '2026-10-02', 'status' => 'falta']);

        $report = $this->actingAs($parent, 'sanctum')
            ->getJson("/api/v1/app/children/{$student->id}/summary")->assertOk()->json('data.report');

        $this->assertEquals(80, $report['average']);
        $this->assertEquals(50, $report['attendance_percent']);
        $this->assertSame(
            [['quarter' => 1, 'average' => 90], ['quarter' => 2, 'average' => 70], ['quarter' => 3, 'average' => null]],
            $report['quarter_averages']
        );
        $this->assertSame('Quechua', $report['subjects'][0]['name']);
    }

    public function test_parent_cannot_read_another_familys_student(): void
    {
        $parent = $this->user('padre');
        $student = $this->student('Hijo Ajeno', $this->user('madre'));

        $this->actingAs($parent, 'sanctum')->getJson("/api/v1/app/children/{$student->id}/summary")->assertForbidden();
    }

    public function test_inactive_family_link_cannot_read_summary(): void
    {
        $tutor = $this->user('tutor');
        $student = $this->student('Luis Rojas');
        $tutor->familyStudents()->attach($student->id, [
            'relation_type' => 'tutor', 'is_primary' => false, 'can_view' => true,
            'can_edit' => false, 'can_receive_reports' => true, 'active' => false,
        ]);

        $this->actingAs($tutor, 'sanctum')->getJson("/api/v1/app/children/{$student->id}/summary")->assertForbidden();
        $this->assertSame([], $this->actingAs($tutor, 'sanctum')->getJson('/api/v1/app/children')->json('data'));
    }

    public function test_unknown_student_returns_404(): void
    {
        $this->actingAs($this->user('padre'), 'sanctum')->getJson('/api/v1/app/children/9999/summary')->assertNotFound();
    }

    public function test_non_family_roles_are_rejected(): void
    {
        $student = $this->student('Ana Paz');

        foreach (['admin', 'profesor', 'estudiante', 'robot'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/app/children')->assertForbidden();
            $this->actingAs($user, 'sanctum')->getJson("/api/v1/app/children/{$student->id}/summary")->assertForbidden();
        }
    }

    public function test_me_returns_only_basic_profile(): void
    {
        $parent = $this->user('padre', ['name' => 'Juan Quispe']);

        $this->actingAs($parent, 'sanctum')->getJson('/api/v1/app/me')->assertOk()
            ->assertJsonPath('data.name', 'Juan Quispe')
            ->assertJsonPath('data.role', 'padre')
            ->assertJsonMissingPath('data.password');
    }
}
