<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\NotebookColumn;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\NotebookColumns;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Casillas SER, SABER y HACER del cuaderno: empiezan en 2, solo el profesor de la materia y el paralelo
 * agrega o descarta, una casilla con notas no se descarta y el PDF usa las mismas casillas.
 */
class NotebookColumnsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Subject $subject;

    private Parallel $parallel;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $this->subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        $course = Course::firstOrCreate(['name' => 'Sexto de Primaria']);
        $this->parallel = Parallel::firstOrCreate(['name' => 'A', 'course_id' => $course->id], ['max_students' => 30]);
        TeachingAssignment::create([
            'teacher_user_id' => $this->teacher->id, 'subject_id' => $this->subject->id,
            'parallel_id' => $this->parallel->id, 'active' => true,
        ]);
        $this->student = Student::create([
            'name' => 'Ana Quispe', 'course' => $course->name, 'course_id' => $course->id, 'parallel_id' => $this->parallel->id,
        ]);
    }

    private function change(User $user, string $dimension, string $action, int $quarter = 1)
    {
        return $this->actingAs($user)->postJson(route('grades.columns'), [
            'subject_id' => $this->subject->id, 'parallel_id' => $this->parallel->id,
            'quarter' => $quarter, 'dimension' => $dimension, 'action' => $action,
        ]);
    }

    private function visible(int $quarter = 1): array
    {
        return app(NotebookColumns::class)->visible($this->subject->id, $this->parallel->id, $quarter);
    }

    private function grade(string $type, int $number, int $score = 80): void
    {
        Grade::create([
            'student_id' => $this->student->id, 'subject_id' => $this->subject->id, 'type' => $type,
            'activity_number' => $number, 'quarter' => 1, 'score' => $score, 'status' => 'completed',
        ]);
    }

    public function test_each_dimension_starts_with_two_boxes(): void
    {
        $this->assertSame(['ser' => 2, 'saber' => 2, 'hacer' => 2], $this->visible());

        $html = $this->actingAs($this->teacher)
            ->get("/grades?subject_id={$this->subject->id}&parallel_id={$this->parallel->id}&quarter=1")
            ->assertOk()
            ->assertSee('Agregar otra casilla SER')
            ->assertSee('Descartar la última casilla SABER')
            ->getContent();

        $this->assertMatchesRegularExpression('/class="text-center dim-col d-none" data-dim="saber" data-col="3"/', $html);
        $this->assertMatchesRegularExpression('/class="text-center dim-col " data-dim="saber" data-col="2"/', $html);
    }

    public function test_teacher_adds_and_discards_per_quarter(): void
    {
        $this->change($this->teacher, 'saber', 'add')->assertOk()->assertJson(['success' => true, 'count' => 3]);
        $this->change($this->teacher, 'saber', 'add')->assertJson(['count' => 4]);
        $this->change($this->teacher, 'ser', 'remove')->assertJson(['count' => 1]);

        $this->assertSame(['ser' => 1, 'saber' => 4, 'hacer' => 2], $this->visible());
        $this->assertSame(['ser' => 2, 'saber' => 2, 'hacer' => 2], $this->visible(2), 'cada trimestre por separado');
    }

    public function test_limits_are_respected(): void
    {
        $this->change($this->teacher, 'ser', 'add')->assertJson(['count' => 3]);
        $this->change($this->teacher, 'ser', 'add')->assertStatus(422)->assertJsonPath('success', false);

        $this->change($this->teacher, 'hacer', 'remove')->assertJson(['count' => 1]);
        $this->change($this->teacher, 'hacer', 'remove')->assertStatus(422);
    }

    public function test_a_box_with_grades_cannot_be_discarded_and_always_shows(): void
    {
        $this->grade('exam', 2);
        $this->change($this->teacher, 'saber', 'remove')
            ->assertStatus(422)
            ->assertJsonPath('message', 'La casilla SABER 2 tiene notas; bórralas antes de descartarla.');

        // El robot llenó la casilla HACER 4: se ve aunque el profesor tenga 2.
        $this->grade('robot', 4);
        $this->assertSame(4, $this->visible()['hacer']);
    }

    public function test_only_the_assigned_teacher_can_change_boxes(): void
    {
        $otherTeacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $this->change($otherTeacher, 'ser', 'add')->assertStatus(422);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->assertNotSame(200, $this->change($admin, 'ser', 'add')->status(), 'el rol:profesor no deja pasar al administrador');
        $this->actingAs($admin)
            ->get("/grades?subject_id={$this->subject->id}&parallel_id={$this->parallel->id}&quarter=1")
            ->assertOk()
            ->assertDontSee('Agregar otra casilla SER');

        $this->assertSame(0, NotebookColumn::count());
    }

    public function test_pdf_uses_the_same_boxes(): void
    {
        $this->change($this->teacher, 'saber', 'add');
        $this->grade('exam', 3, 77);

        $response = $this->actingAs($this->teacher)
            ->get("/grades/export-pdf?subject_id={$this->subject->id}&parallel_id={$this->parallel->id}&quarter=1")
            ->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));

        $html = view('reports.grades_pdf', [
            'columns' => $this->visible(), 'students' => collect([$this->student]), 'gradesByStudent' => collect(),
            'subject' => $this->subject, 'parallel' => $this->parallel, 'selectedQuarter' => 1,
        ])->render();
        $this->assertStringContainsString('<th colspan="2" class="grupo-ser">', $html);
        $this->assertStringContainsString('<th colspan="3" class="grupo-saber">', $html);
        $this->assertStringContainsString('<th colspan="2" class="grupo-hacer">', $html);
    }
}
