<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use App\Models\Subject;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\CoursePromotion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cierre de gestión → promoción: vienen marcados quienes aprobaron la gestión anterior (nota final de 51
 * o más), los que no llegan quedan desmarcados (repiten) y el último curso se registra como egresado.
 */
class PromotionByFinalGradeTest extends TestCase
{
    use RefreshDatabase;

    private AcademicYear $y2026;

    private AcademicYear $y2027;

    private Parallel $from;

    private Parallel $to;

    protected function setUp(): void
    {
        parent::setUp();
        $this->y2026 = AcademicYear::current();
        $this->y2027 = AcademicYear::create(['year' => 2027, 'name' => 'Gestión 2027', 'is_current' => false]);
        $this->from = $this->parallel('Tercero');
        $this->to = $this->parallel('Cuarto');
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function parallel(string $course): Parallel
    {
        $c = Course::firstOrCreate(['name' => $course]);

        return Parallel::firstOrCreate(['name' => 'A', 'course_id' => $c->id], ['max_students' => 25]);
    }

    private function student(string $name, array $scores = []): Student
    {
        $student = Student::create([
            'name' => $name, 'course' => $this->from->course->name, 'course_id' => $this->from->course_id,
            'parallel_id' => $this->from->id, 'age' => 9,
        ]);
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        foreach ($scores as $i => $score) {
            Grade::create([
                'student_id' => $student->id, 'subject_id' => $subject->id, 'score' => $score, 'type' => 'activity',
                'status' => 'completed', 'quarter' => 1, 'activity_number' => $i + 1,
            ]);
        }

        return $student;
    }

    private function startNewYear(): void
    {
        app(AcademicYearService::class)->close(null, $this->y2026, $this->y2027);
    }

    public function test_closing_the_current_year_leads_to_the_promotion_screen(): void
    {
        $this->actingAs($this->user('director'))
            ->post("/admin/years/{$this->y2026->id}/close", ['next_year_id' => $this->y2027->id])
            ->assertRedirect(route('students.promote'))
            ->assertSessionHas('success');

        $this->assertTrue($this->y2027->fresh()->is_current);
    }

    public function test_final_average_of_the_previous_year_decides_who_comes_checked(): void
    {
        $aprueba = $this->student('Ana Quispe', [70, 60]);   // 65
        $justo = $this->student('Luis Mamani', [51]);        // 51: aprueba
        $repite = $this->student('Rosa Choque', [40, 50]);   // 45
        $sinNotas = $this->student('Juan Nina');
        $this->startNewYear();

        $averages = app(CoursePromotion::class)->finalAverages($this->y2026, [$aprueba->id, $justo->id, $repite->id, $sinNotas->id]);
        $this->assertSame([$aprueba->id => 65.0, $justo->id => 51.0, $repite->id => 45.0], $averages);

        $html = $this->actingAs($this->user('director'))
            ->get(route('students.promote', ['from' => $this->from->id]))
            ->assertOk()
            ->assertSee('Nota final 2026')
            ->assertSee('65.00 · Aprueba')
            ->assertSee('45.00 · Repite')
            ->assertSee('Sin notas')
            ->assertSee('1 estudiante(s) no llegan a 51')
            ->getContent();

        $this->assertMatchesRegularExpression('/value="' . $aprueba->id . '" class="promote-student" id="st-\d+"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/value="' . $justo->id . '" class="promote-student" id="st-\d+"\s+checked/', $html);
        $this->assertMatchesRegularExpression('/value="' . $repite->id . '" class="promote-student" id="st-\d+"\s*>/', $html);
    }

    public function test_promotion_works_after_closing_even_with_last_year_grades(): void
    {
        $ana = $this->student('Ana Quispe', [80]);
        $this->startNewYear();

        $this->actingAs($this->user('director'))->post(route('students.promote.store'), [
            'from_parallel_id' => $this->from->id, 'to_parallel_id' => $this->to->id, 'student_ids' => [$ana->id],
        ])->assertSessionHas('success');

        $this->assertSame($this->to->id, $ana->fresh()->parallel_id);
    }

    public function test_last_course_graduates_and_keeps_its_history(): void
    {
        $ana = $this->student('Ana Quispe', [80, 90]);
        $luis = $this->student('Luis Mamani', [70]);
        $this->startNewYear();

        $this->actingAs($this->user('director'))->post(route('students.promote.store'), [
            'from_parallel_id' => $this->from->id, 'to_parallel_id' => 'egreso', 'student_ids' => [$ana->id],
        ])->assertRedirect(route('students.promote'))->assertSessionHas('success');

        $this->assertTrue($ana->fresh()->isWithdrawn());
        $this->assertFalse($luis->fresh()->isWithdrawn());

        $baja = StudentWithdrawal::where('student_id', $ana->id)->firstOrFail();
        $this->assertSame('egreso', $baja->type);
        $this->assertSame('Egresado', $baja->shortLabel());
        $this->assertSame(85.0, (float) $baja->snapshot['report']['average'], 'el respaldo guarda las notas de 2026');
        $this->assertCount(2, $baja->snapshot['grades']);
        $this->assertSame(1, AuditLog::where('action', 'students.graduate')->count());

        $this->get('/students?withdrawn=1')->assertOk();
    }

    public function test_the_administrative_panel_cannot_graduate(): void
    {
        $ana = $this->student('Ana Quispe', [80]);
        $this->startNewYear();

        $this->actingAs($this->user('academic'))->post(route('students.promote.store'), [
            'from_parallel_id' => $this->from->id, 'to_parallel_id' => 'egreso', 'student_ids' => [$ana->id],
        ])->assertForbidden();

        $this->assertFalse($ana->fresh()->isWithdrawn());
    }
}
