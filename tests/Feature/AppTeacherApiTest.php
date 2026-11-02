<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** API de la app móvil para el profesor: asistencia y cuaderno de notas. */
class AppTeacherApiTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Subject $subject;
    private Parallel $parallel;
    private Parallel $otherParallel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['rol' => 'profesor', 'estado' => 'activo']);
        $this->subject = Subject::where('code', 'QUE')->firstOrFail();
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $this->parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 40]);
        $this->otherParallel = Parallel::create(['name' => 'B', 'course_id' => $course->id, 'max_students' => 40]);
        TeachingAssignment::create(['teacher_user_id' => $this->teacher->id, 'subject_id' => $this->subject->id, 'parallel_id' => $this->parallel->id, 'active' => true]);
    }

    private function student(string $last, string $first, ?Parallel $parallel = null): Student
    {
        $parallel ??= $this->parallel;

        return Student::create([
            'last_name' => $last, 'first_name' => $first, 'course' => 'Sexto de Primaria',
            'course_id' => $parallel->course_id, 'parallel_id' => $parallel->id, 'status' => 'activo',
        ]);
    }

    private function api(?User $user = null)
    {
        return $this->actingAs($user ?? $this->teacher, 'sanctum');
    }

    public function test_teacher_gets_their_assignments(): void
    {
        $this->student('Quispe', 'Ana');

        $this->api()->getJson('/api/v1/app/teacher/assignments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.subject', $this->subject->name)
            ->assertJsonPath('data.0.parallel', 'A')
            ->assertJsonPath('data.0.students', 1);
    }

    public function test_only_teachers_can_use_these_routes(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        $this->api($admin)->getJson('/api/v1/app/teacher/assignments')->assertForbidden();
    }

    public function test_attendance_list_and_saving_the_whole_day(): void
    {
        $ana = $this->student('Quispe', 'Ana');
        $luis = $this->student('Alanes', 'Luis');
        $query = "subject_id={$this->subject->id}&parallel_id={$this->parallel->id}&date=" . now()->toDateString();

        $this->api()->getJson("/api/v1/app/teacher/attendance?{$query}")
            ->assertOk()
            ->assertJsonPath('data.students.0.name', 'Alanes Luis') // orden de lista
            ->assertJsonPath('data.students.0.number', 1)
            ->assertJsonPath('data.students.0.status', null)
            ->assertJsonPath('data.statuses', Attendance::STATUSES);

        $this->api()->postJson('/api/v1/app/teacher/attendance', [
            'subject_id' => $this->subject->id,
            'date' => now()->toDateString(),
            'records' => [['student_id' => $ana->id, 'status' => 'Presente'], ['student_id' => $luis->id, 'status' => 'Licencia']],
        ])->assertOk()->assertJsonPath('data.saved', 2);

        $this->api()->getJson("/api/v1/app/teacher/attendance?{$query}")
            ->assertJsonPath('data.students.0.status', 'Licencia')
            ->assertJsonPath('data.students.1.status', 'Presente');
    }

    public function test_attendance_rejects_future_dates_and_other_parallels(): void
    {
        $foreign = $this->student('Ajeno', 'Pedro', $this->otherParallel);

        $this->api()->getJson("/api/v1/app/teacher/attendance?subject_id={$this->subject->id}&parallel_id={$this->otherParallel->id}")
            ->assertForbidden();
        $this->api()->postJson('/api/v1/app/teacher/attendance', [
            'subject_id' => $this->subject->id, 'date' => now()->toDateString(),
            'records' => [['student_id' => $foreign->id, 'status' => 'Presente']],
        ])->assertForbidden();
        $this->api()->postJson('/api/v1/app/teacher/attendance', [
            'subject_id' => $this->subject->id, 'date' => now()->addDay()->toDateString(),
            'records' => [['student_id' => $this->student('Quispe', 'Ana')->id, 'status' => 'Presente']],
        ])->assertStatus(422);

        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_grades_notebook_and_saving_with_the_web_rules(): void
    {
        $ana = $this->student('Quispe', 'Ana');
        $save = fn (array $over) => $this->api()->postJson('/api/v1/app/teacher/grades', $over + [
            'student_id' => $ana->id, 'subject_id' => $this->subject->id, 'type' => 'activity', 'activity_number' => 1, 'quarter' => 1,
        ]);

        $save(['score' => 80])->assertOk()->assertJsonPath('data.score', 80);
        $save(['score' => 345])->assertStatus(422);
        $save(['score' => 87.5])->assertStatus(422);
        $save(['score' => 0])->assertStatus(422);
        $save(['type' => 'autoeval', 'score' => 6])->assertStatus(422);
        $save(['type' => 'autoeval', 'score' => 4])->assertOk();
        $save(['type' => 'robot', 'score' => 90])->assertStatus(422); // HACER lo llena el robot

        // Corregir una nota deja historial, como en la web.
        $save(['score' => 90])->assertOk();
        $this->assertDatabaseHas('grade_history', ['old_score' => 80, 'new_score' => 90]);

        $notebook = $this->api()->getJson("/api/v1/app/teacher/grades?subject_id={$this->subject->id}&parallel_id={$this->parallel->id}&quarter=1")
            ->assertOk()->json('data');
        $this->assertSame(90.0, (float) $notebook['students'][0]['scores']['activity'][1]);
        // SER 90/100 * 10 = 9 puntos + autoevaluación 4 = 13.
        $this->assertEquals(9, $notebook['students'][0]['totals']['ser']);
        $this->assertEquals(13, $notebook['students'][0]['totals']['final']);
        $this->assertFalse(collect($notebook['columns'])->firstWhere('type', 'robot')['editable']);
    }

    public function test_closed_year_does_not_accept_changes(): void
    {
        $ana = $this->student('Quispe', 'Ana');
        AcademicYear::where('is_current', true)->update(['closed_at' => now()]);

        $this->api()->postJson('/api/v1/app/teacher/grades', [
            'student_id' => $ana->id, 'subject_id' => $this->subject->id, 'type' => 'activity',
            'activity_number' => 1, 'quarter' => 1, 'score' => 80,
        ])->assertStatus(409);
        $this->assertSame(0, Grade::count());
    }
}
