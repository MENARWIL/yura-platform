<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Gestión escolar: 2026 es la actual; los datos y las matrículas se ligan a su gestión. */
class AcademicYearTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function student(string $name = 'Ana Uno', ?User $parent = null): Student
    {
        $course = Course::firstOrCreate(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::firstOrCreate(['course_id' => $course->id, 'name' => 'A'], ['max_students' => 40]);

        return Student::create([
            'name' => $name, 'course' => $course->name, 'course_id' => $course->id, 'parallel_id' => $parallel->id,
            'parent_user_id' => $parent?->id, 'status' => 'activo',
        ]);
    }

    private function grade(Student $student, float $score, ?int $yearId = null): Grade
    {
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);

        return Grade::create(array_filter([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'score' => $score, 'type' => 'activity',
            'status' => 'completed', 'quarter' => 1, 'academic_year_id' => $yearId,
        ], fn ($v) => $v !== null));
    }

    public function test_2026_exists_and_is_the_current_year(): void
    {
        $current = AcademicYear::current();

        $this->assertSame(2026, $current->year);
        $this->assertSame(1, AcademicYear::where('is_current', true)->count());
    }

    public function test_new_grades_attendance_and_activities_get_the_current_year(): void
    {
        $student = $this->student();
        $grade = $this->grade($student, 80);
        $attendance = Attendance::create(['student_id' => $student->id, 'subject_id' => $grade->subject_id, 'date' => '2026-10-01', 'status' => 'presente']);

        $this->assertSame(AcademicYear::currentId(), $grade->fresh()->academic_year_id);
        $this->assertSame(AcademicYear::currentId(), $attendance->fresh()->academic_year_id);
    }

    public function test_default_queries_only_see_the_current_year(): void
    {
        $student = $this->student();
        $old = AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);
        $this->grade($student, 80);
        $this->grade($student, 40, $old->id);

        $this->assertSame([80.0], Grade::pluck('score')->all());
        $this->assertSame(2, Grade::withoutGlobalScopes()->count());
    }

    public function test_year_id_query_shows_that_years_numbers_in_the_summary(): void
    {
        $admin = $this->user('admin');
        $student = $this->student();
        $old = AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);
        $this->grade($student, 80);
        $this->grade($student, 40, $old->id);

        $now = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/app/students/{$student->id}/summary")->assertOk();
        $past = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/app/students/{$student->id}/summary?year_id={$old->id}")->assertOk();

        $this->assertEquals(80, $now->json('data.report.average'));
        $this->assertEquals(40, $past->json('data.report.average'));
    }

    public function test_family_summary_also_respects_the_year(): void
    {
        $parent = $this->user('padre');
        $student = $this->student('Ana Uno', $parent);
        $old = AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);
        $this->grade($student, 90);
        $this->grade($student, 50, $old->id);

        $this->assertEquals(90, $this->actingAs($parent, 'sanctum')->getJson("/api/v1/app/children/{$student->id}/summary")->json('data.report.average'));
        $this->assertEquals(50, $this->actingAs($parent, 'sanctum')->getJson("/api/v1/app/children/{$student->id}/summary?year_id={$old->id}")->json('data.report.average'));
    }

    public function test_a_new_student_gets_a_current_year_enrollment_that_follows_parallel_changes(): void
    {
        $student = $this->student();
        $enrollment = Enrollment::where('student_id', $student->id)->where('academic_year_id', AcademicYear::currentId())->firstOrFail();
        $this->assertSame($student->parallel_id, $enrollment->parallel_id);

        $b = Parallel::create(['course_id' => $student->course_id, 'name' => 'B', 'max_students' => 40]);
        $student->update(['parallel_id' => $b->id]);

        $this->assertSame(1, Enrollment::where('student_id', $student->id)->count(), 'no debe duplicar la matrícula');
        $this->assertSame($b->id, $enrollment->fresh()->parallel_id);
    }

    public function test_years_endpoint_lists_them_with_the_current_marked(): void
    {
        AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);

        foreach (['admin', 'profesor', 'padre'] as $role) {
            $data = $this->actingAs($this->user($role), 'sanctum')->getJson('/api/v1/app/years')->assertOk()->json('data');
            $this->assertSame([2026, 2025], collect($data)->pluck('year')->all());
            $this->assertSame([true, false], collect($data)->pluck('is_current')->all());
        }
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/app/years')->assertUnauthorized();
    }

    public function test_students_list_by_year_uses_that_years_enrollment(): void
    {
        $admin = $this->user('admin');
        $old = AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);
        $ana = $this->student('Ana Uno');
        $this->student('Beto Dos');
        $quinto = Course::create(['name' => 'Quinto de Primaria']);
        $parallel5 = Parallel::create(['course_id' => $quinto->id, 'name' => 'A', 'max_students' => 40]);
        Enrollment::create(['student_id' => $ana->id, 'academic_year_id' => $old->id, 'course_id' => $quinto->id, 'parallel_id' => $parallel5->id]);

        $names = fn ($qs) => collect($this->actingAs($admin, 'sanctum')->getJson("/api/v1/app/students?$qs")->json('data'))->pluck('name')->sort()->values()->all();

        $this->assertSame(['Ana Uno', 'Beto Dos'], $names(''));
        $this->assertSame(['Ana Uno'], $names("year_id={$old->id}"));
        $this->assertSame(['Ana Uno'], $names("year_id={$old->id}&course_id={$quinto->id}"));
        $this->assertSame([], $names("year_id={$old->id}&course_id={$ana->course_id}"));
    }
}
