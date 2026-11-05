<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\TrimesterReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Documentos de secretaría: constancia de estudiante regular y boletín de notas trimestrales (PDF),
 * y planilla de notas trimestrales del paralelo (PDF y Excel).
 */
class SecretaryDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private Subject $quechua;

    private Parallel $parallel;

    private Student $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $course = Course::firstOrCreate(['name' => '6° Primaria']);
        $this->parallel = Parallel::firstOrCreate(['name' => 'A', 'course_id' => $course->id], ['max_students' => 25]);
        $this->quechua = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        $this->ana = Student::create([
            'name' => 'Ana Paz', 'course' => '6° Primaria', 'course_id' => $course->id, 'parallel_id' => $this->parallel->id,
            'edad' => 11, 'genero' => 'Femenino', 'nivel' => 'intermedio', 'registration_date' => '2026-05-25', 'puntaje' => 80,
        ]);
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function grade(Subject $subject, int $quarter, string $type, int $number, float $score): void
    {
        Grade::create([
            'student_id' => $this->ana->id, 'subject_id' => $subject->id, 'score' => $score, 'type' => $type,
            'status' => 'completed', 'quarter' => $quarter, 'activity_number' => $number, 'is_robot_activity' => $type === 'robot',
        ]);
    }

    private function assertPdf($response): void
    {
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_administration_downloads_the_regular_student_certificate(): void
    {
        foreach (['admin', 'director', 'academic'] as $role) {
            $this->assertPdf($this->actingAs($this->user($role))->get(route('students.constancia', $this->ana)));
        }
    }

    public function test_certificate_is_not_issued_for_a_withdrawn_student(): void
    {
        $this->user('admin');
        $secretary = $this->user('academic');
        $this->actingAs($secretary)->post(route('students.withdraw', $this->ana), ['motive' => 'abandono', 'withdrawn_on' => now()->toDateString()]);

        $this->actingAs($secretary)->get(route('students.constancia', $this->ana))
            ->assertRedirect()
            ->assertSessionHas('error', 'El estudiante está dado de baja: no se puede emitir una constancia de estudiante regular.');
    }

    public function test_other_roles_cannot_download_student_documents(): void
    {
        foreach (['profesor', 'padre'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user)->get(route('students.constancia', $this->ana))->assertRedirect('/');
            $this->actingAs($user)->get(route('students.boletin', $this->ana))->assertRedirect('/');
        }
    }

    public function test_administration_downloads_the_report_card(): void
    {
        $this->grade($this->quechua, 1, 'exam', 1, 100);

        foreach (['admin', 'director', 'academic'] as $role) {
            $this->assertPdf($this->actingAs($this->user($role))->get(route('students.boletin', $this->ana)));
        }
    }

    public function test_report_card_has_each_subject_with_its_trimesters_and_annual_average(): void
    {
        $math = Subject::firstOrCreate(['code' => 'MAT'], ['name' => 'Matemática']);
        // Quechua: 1.er trimestre SABER 100 → 45; 2.º trimestre SER 100 → 10.
        $this->grade($this->quechua, 1, 'exam', 1, 100);
        $this->grade($this->quechua, 2, 'activity', 1, 100);
        // Matemática: 3.er trimestre HACER 50 + autoevaluación 4 → 24.
        $this->grade($math, 3, 'robot', 1, 50);
        $this->grade($math, 3, 'autoeval', 1, 4);

        $rows = TrimesterReport::forStudent($this->ana)->keyBy(fn ($row) => $row['subject']->name);

        $this->assertSame([1 => 45.0, 2 => 10.0, 3 => null], $rows['Quechua']['quarters']);
        $this->assertSame(27.5, $rows['Quechua']['annual']);
        $this->assertSame([1 => null, 2 => null, 3 => 24.0], $rows['Matemática']['quarters']);
        $this->assertSame(24.0, $rows['Matemática']['annual']);
        $this->assertSame(25.75, TrimesterReport::columnAverage($rows->values()));
    }

    public function test_director_and_secretary_download_the_parallel_sheet_as_pdf_and_excel(): void
    {
        $this->grade($this->quechua, 1, 'exam', 1, 100);
        $query = ['subject_id' => $this->quechua->id, 'parallel_id' => $this->parallel->id];

        foreach (['director', 'academic'] as $role) {
            $user = $this->user($role);
            $this->assertPdf($this->actingAs($user)->get(route('grades.trimesters.pdf', $query)));

            $excel = $this->actingAs($user)->get(route('grades.trimesters.excel', $query))->assertOk();
            $this->assertStringContainsString('.xlsx', $excel->headers->get('content-disposition'));

            $this->actingAs($user)->get(route('grades.trimesters', $query))->assertOk()
                ->assertSee(route('grades.trimesters.pdf', $query)) // el «&» va escapado como &amp; en el HTML
                ->assertSee(route('students.boletin', $this->ana), false);
        }
    }

    public function test_parallel_sheet_needs_subject_and_parallel_and_is_not_for_other_roles(): void
    {
        $director = $this->user('director');
        $this->actingAs($director)->get(route('grades.trimesters.pdf'))->assertSessionHasErrors(['subject_id', 'parallel_id']);

        $query = ['subject_id' => $this->quechua->id, 'parallel_id' => $this->parallel->id];
        foreach (['admin', 'profesor'] as $role) {
            $this->actingAs($this->user($role))->get(route('grades.trimesters.excel', $query))->assertRedirect('/');
        }
    }

    public function test_student_page_offers_the_documents_to_administration_only(): void
    {
        $this->actingAs($this->user('academic'))->get(route('grades.show', $this->ana))->assertOk()
            ->assertSee(route('students.constancia', $this->ana), false)
            ->assertSee(route('students.boletin', $this->ana), false);
    }
}
