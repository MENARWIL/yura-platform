<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\CuadernoNota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Puntos del Cuaderno Pedagógico: SER 10, SABER 45 y HACER 40 suman 95; con la autoevaluación (5)
 * la nota del trimestre es sobre 100. Los encabezados de la web y del PDF dicen lo mismo.
 */
class NotebookDimensionPointsTest extends TestCase
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

    public function test_full_marks_give_95_before_and_100_after_the_self_evaluation(): void
    {
        $all = fn (int $count) => array_fill(1, $count, ['score' => 100]);
        $nota = CuadernoNota::summarize([
            'activity' => $all(3), 'exam' => $all(5), 'robot' => $all(5), 'autoeval' => [1 => ['score' => 5]],
        ]);

        $this->assertEquals([10, 45, 40], [$nota['prom_ser'], $nota['prom_saber'], $nota['prom_hacer']]);
        $this->assertEquals(95, $nota['total']);
        $this->assertEquals(100, $nota['nota']);
    }

    public function test_web_notebook_headers_show_the_points_and_the_trimester(): void
    {
        $html = $this->actingAs($this->teacher)
            ->get("/grades?subject_id={$this->subject->id}&parallel_id={$this->parallel->id}&quarter=1")
            ->assertOk()
            ->assertSee('PROMEDIO<br>DIMENSIÓN<br>(10 PTS)', false)
            ->assertSee('PROMEDIO<br>DIMENSIÓN<br>(45 PTS)', false)
            ->assertSee('PROMEDIO<br>DIMENSIÓN<br>(40 PTS)', false)
            ->assertSee('TOTAL<br>(95 PTS)', false)
            ->assertSee('AUTOEVALUACIÓN<br>TOTAL (5 PTS)', false)
            ->assertSee('NOTA<br>TRIMESTRAL<br>(100 PTS)', false)
            ->getContent();

        $this->assertStringNotContainsString('90 PTS', $html);
        $this->assertStringNotContainsStringIgnoringCase('bimestral', $html);
    }

    public function test_pdf_notebook_headers_show_the_points_and_the_trimester(): void
    {
        $this->actingAs($this->teacher);
        $html = view('reports.grades_pdf', [
            'columns' => ['ser' => 2, 'saber' => 2, 'hacer' => 2], 'students' => collect([$this->student]), 'gradesByStudent' => collect(),
            'subject' => $this->subject, 'parallel' => $this->parallel, 'selectedQuarter' => 1,
        ])->render();

        foreach (['Prom. SER<br>(10)', 'Prom. SABER<br>(45)', 'Prom. HACER<br>(40)', 'TOTAL<br>(95)', 'AUTOEVAL.<br>(5)', 'NOTA<br>TRIMESTRAL<br>(100)'] as $header) {
            $this->assertStringContainsString($header, $html);
        }
        $this->assertStringNotContainsString('(90)', $html);
        $this->assertStringNotContainsStringIgnoringCase('bimestral', $html);
    }
}
