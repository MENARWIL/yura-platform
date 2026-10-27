<?php

namespace Tests\Feature;

use App\Exports\AcademicYearExport;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\ClosedAcademicYearException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Consulta y respaldo de una gestión (también de una cerrada). Solo el administrador.
 * Leer nunca reabre ni desbloquea una gestión cerrada.
 */
class AcademicYearArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'active'], $extra));
    }

    private function parallel(string $name): Parallel
    {
        $course = Course::firstOrCreate(['name' => 'Sexto de Primaria']);

        return Parallel::firstOrCreate(['course_id' => $course->id, 'name' => $name], ['max_students' => 40]);
    }

    private function student(string $last, string $second, string $first, Parallel $parallel): Student
    {
        return Student::create([
            'name' => "$last $second $first", 'last_name' => $last, 'second_last_name' => $second, 'first_name' => $first,
            'course' => $parallel->course->name, 'course_id' => $parallel->course_id, 'parallel_id' => $parallel->id, 'status' => 'activo',
        ]);
    }

    /** 2026 cerrada con datos y 2027 actual con otros datos. Devuelve [2026, 2027, admin]. */
    private function closedYearWithData(): array
    {
        $admin = $this->user('admin', ['name' => 'Administrador Principal']);
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        $a = $this->parallel('A');
        $b = $this->parallel('B');
        $y2026 = AcademicYear::current();

        // Se crean fuera de orden alfabético a propósito.
        $quispe = $this->student('Quispe', 'Lima', 'Juan', $a);
        $alanes = $this->student('Alanes', 'Cruz', 'Raul', $a);
        $zeta = $this->student('Zeta', 'Paz', 'Ana', $b);

        foreach ([[$quispe, 80], [$alanes, 55], [$zeta, 91]] as [$student, $score]) {
            Grade::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'score' => $score, 'type' => 'activity', 'status' => 'completed', 'quarter' => 1, 'activity_number' => 1]);
        }
        Attendance::create(['student_id' => $alanes->id, 'subject_id' => $subject->id, 'date' => '2026-03-10', 'status' => 'Presente']);

        $y2027 = app(AcademicYearService::class)->create($admin, 2027);
        app(AcademicYearService::class)->close($admin, $y2026, $y2027);

        // Datos de la gestión nueva: no deben aparecer en el respaldo de 2026.
        $nuevo = $this->student('Nuevo', 'Solo', '2027', $a);
        Grade::create(['student_id' => $nuevo->id, 'subject_id' => $subject->id, 'score' => 99, 'type' => 'activity', 'status' => 'completed', 'quarter' => 1, 'activity_number' => 1]);

        return [$y2026->fresh(), $y2027->fresh(), $admin];
    }

    /** @return array<string, array<int, array<int, mixed>>> hojas del Excel: título => filas (con encabezado) */
    private function sheetsOf(AcademicYear $year): array
    {
        $raw = Excel::raw(new AcademicYearExport($year), \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'yr') . '.xlsx';
        file_put_contents($path, $raw);
        try {
            $book = IOFactory::load($path);
            $sheets = [];
            foreach ($book->getSheetNames() as $name) {
                $sheets[$name] = $book->getSheetByName($name)->toArray();
            }

            return $sheets;
        } finally {
            @unlink($path);
        }
    }

    public function test_only_the_admin_reaches_the_consult_and_export(): void
    {
        [$y2026] = $this->closedYearWithData();

        foreach (['academic', 'profesor', 'padre', 'madre', 'tutor', 'estudiante'] as $role) {
            $this->actingAs($this->user($role))->get("/admin/years/{$y2026->id}")->assertRedirect('/')->assertSessionHas('error');
            $this->actingAs($this->user($role))->get("/admin/years/{$y2026->id}/export")->assertRedirect('/')->assertSessionHas('error');
        }
        $this->assertSame(0, AuditLog::where('action', 'year.export')->count(), 'una descarga bloqueada no debe quedar como hecha');

        $this->actingAs($this->user('admin'))->get("/admin/years/{$y2026->id}")->assertOk();
    }

    public function test_the_consult_shows_a_closed_year_as_it_was_saved(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();

        $response = $this->actingAs($admin)->get("/admin/years/{$y2026->id}")->assertOk();

        $response->assertSee('Gestión 2026')
            ->assertSee('Cerrada')
            ->assertSee('Administrador Principal')          // quién la cerró
            ->assertSee('nadie puede modificarlos')
            ->assertSee('Descargar respaldo')
            ->assertSee('Reabrir para editar')
            ->assertSee('Alanes Cruz Raul')
            ->assertSee('Quispe Lima Juan')
            ->assertSee('Zeta Paz Ana')
            ->assertDontSee('Nuevo Solo 2027');             // es de la gestión 2027

        // Lista alfabética por paralelo: Alanes antes que Quispe.
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'Quispe Lima Juan'), strpos($html, 'Alanes Cruz Raul'));
    }

    public function test_the_consult_does_not_reopen_the_year_and_writes_stay_blocked(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();
        $student = Student::where('name', 'like', 'Alanes%')->firstOrFail();

        $this->actingAs($admin)->get("/admin/years/{$y2026->id}")->assertOk();
        $this->actingAs($admin)->get("/admin/years/{$y2026->id}/export")->assertOk();

        $this->assertTrue($y2026->fresh()->isClosed(), 'consultar o descargar no debe reabrir la gestión');

        $this->expectException(ClosedAcademicYearException::class);
        Grade::create(['student_id' => $student->id, 'subject_id' => Subject::first()->id, 'score' => 1, 'type' => 'activity', 'status' => 'completed', 'quarter' => 1, 'academic_year_id' => $y2026->id]);
    }

    public function test_the_export_has_only_that_years_data_in_three_sheets(): void
    {
        [$y2026] = $this->closedYearWithData();

        $sheets = $this->sheetsOf($y2026);

        $this->assertSame(['Matrículas', 'Notas', 'Asistencia'], array_keys($sheets));

        // Matrículas: numeradas dentro de cada paralelo y en orden alfabético.
        $enrollments = array_slice($sheets['Matrículas'], 1);
        $this->assertCount(3, $enrollments, 'solo los 3 estudiantes de 2026');
        $this->assertSame(
            [['Sexto de Primaria', 'A', 1, 'Alanes Cruz Raul'], ['Sexto de Primaria', 'A', 2, 'Quispe Lima Juan'], ['Sexto de Primaria', 'B', 1, 'Zeta Paz Ana']],
            array_map(fn ($r) => [$r[0], $r[1], (int) $r[2], $r[3]], $enrollments)
        );

        // Notas: las 3 de 2026, sin la de 2027 (99).
        $grades = array_slice($sheets['Notas'], 1);
        $this->assertCount(3, $grades);
        $scores = array_map(fn ($r) => (float) $r[7], $grades);
        sort($scores);
        $this->assertSame([55.0, 80.0, 91.0], $scores);
        $this->assertNotContains(99.0, $scores);

        // Asistencia: el único registro.
        $attendance = array_slice($sheets['Asistencia'], 1);
        $this->assertCount(1, $attendance);
        $this->assertSame('10/03/2026', $attendance[0][0]);
        $this->assertSame('Alanes Cruz Raul', $attendance[0][3]);
    }

    public function test_the_export_download_is_audited_and_named_by_year(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();

        $response = $this->actingAs($admin)->get("/admin/years/{$y2026->id}/export")->assertOk();

        $this->assertStringContainsString('gestion_2026_respaldo_', $response->headers->get('content-disposition'));
        $log = AuditLog::where('action', 'year.export')->where('entity_id', $y2026->id)->firstOrFail();
        $this->assertSame($admin->id, (int) $log->user_id);
    }

    /** Añade a 2026 (ya cerrada) un curso más con un estudiante matriculado. Devuelve [curso, paralelo]. */
    private function addSecondCourseTo(AcademicYear $year): array
    {
        $course = Course::create(['name' => 'Quinto de Primaria']);
        $parallel = Parallel::create(['course_id' => $course->id, 'name' => 'C', 'max_students' => 40]);
        $student = $this->student('Lopez', 'Vega', 'Eva', $parallel);
        // La matrícula se crea sola en la gestión actual (2027); se mueve a 2026 para esta prueba.
        \App\Models\Enrollment::where('student_id', $student->id)->update(['academic_year_id' => $year->id]);

        return [$course, $parallel];
    }

    public function test_the_filters_narrow_the_list_by_parallel_and_by_course(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();
        [$quinto, $quintoC] = $this->addSecondCourseTo($y2026);
        $parallelB = Parallel::where('name', 'B')->firstOrFail();

        // Por paralelo: solo Zeta (paralelo B de Sexto).
        $this->actingAs($admin)->get("/admin/years/{$y2026->id}?parallel_id={$parallelB->id}")->assertOk()
            ->assertSee('Zeta Paz Ana')
            ->assertDontSee('Alanes Cruz Raul')
            ->assertDontSee('Lopez Vega Eva')
            ->assertSee('Mostrando 1 de 4 estudiantes');

        // Por curso: solo Quinto.
        $this->actingAs($admin)->get("/admin/years/{$y2026->id}?course_id={$quinto->id}")->assertOk()
            ->assertSee('Lopez Vega Eva')
            ->assertSee('Quinto de Primaria · Paralelo C')
            ->assertDontSee('Alanes Cruz Raul')
            ->assertDontSee('Zeta Paz Ana');

        // Sin filtros: todos, y sin el aviso «Mostrando».
        $this->actingAs($admin)->get("/admin/years/{$y2026->id}")->assertOk()
            ->assertSee('Alanes Cruz Raul')->assertSee('Lopez Vega Eva')->assertSee('Zeta Paz Ana')
            ->assertDontSee('Mostrando');
    }

    public function test_the_filter_options_only_offer_what_that_year_had(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();
        [$quinto] = $this->addSecondCourseTo($y2026);
        Course::create(['name' => 'Tercero de Primaria']); // existe, pero sin matrículas en 2026

        $html = $this->actingAs($admin)->get("/admin/years/{$y2026->id}")->assertOk()->getContent();

        $this->assertStringContainsString('Sexto de Primaria', $html);
        $this->assertStringContainsString('Quinto de Primaria', $html);
        $this->assertStringNotContainsString('Tercero de Primaria', $html);

        // Con un curso elegido, el paralelo se limita a los de ese curso.
        $filtered = $this->actingAs($admin)->get("/admin/years/{$y2026->id}?course_id={$quinto->id}")->assertOk()->getContent();
        $this->assertStringContainsString('Quinto de Primaria - C', $filtered);
        $this->assertStringNotContainsString('Sexto de Primaria - A', $filtered);
    }

    public function test_the_totals_stay_for_the_whole_year_and_the_export_ignores_the_filters(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();
        $parallelB = Parallel::where('name', 'B')->firstOrFail();

        $html = $this->actingAs($admin)->get("/admin/years/{$y2026->id}?parallel_id={$parallelB->id}")->assertOk()->getContent();

        // Las tarjetas son de toda la gestión (3 matrículas y 3 notas), no solo del paralelo filtrado.
        $this->assertMatchesRegularExpression('/Matrículas.*?>\s*3\s*</s', $html);
        $this->assertMatchesRegularExpression('/Notas.*?>\s*3\s*</s', $html);
        $this->assertStringContainsString('El respaldo de Excel incluye toda la gestión', $html);
        $this->assertCount(3, array_slice($this->sheetsOf($y2026)['Notas'], 1));
    }

    public function test_a_filter_without_matches_shows_a_clear_empty_state_with_a_way_back(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();
        [$quinto] = $this->addSecondCourseTo($y2026);
        $parallelB = Parallel::where('name', 'B')->firstOrFail();

        // El paralelo B es de Sexto; pedirlo junto con el curso Quinto no coincide con nadie.
        $this->actingAs($admin)->get("/admin/years/{$y2026->id}?course_id={$quinto->id}&parallel_id={$parallelB->id}")->assertOk()
            ->assertSee('Ningún estudiante coincide con los filtros elegidos')
            ->assertSee('Limpiar filtros')
            ->assertSee('Mostrando 0 de 4 estudiantes');
    }

    public function test_the_consult_of_a_year_without_enrollments_shows_an_empty_state(): void
    {
        $admin = $this->user('admin');
        $empty = app(AcademicYearService::class)->create($admin, 2030);

        $this->actingAs($admin)->get("/admin/years/{$empty->id}")->assertOk()
            ->assertSee('Gestión 2030')
            ->assertSee('no tiene matrículas registradas')
            ->assertDontSee('Reabrir para editar');
    }

    public function test_the_years_list_links_to_the_consult_and_the_backup(): void
    {
        [$y2026, , $admin] = $this->closedYearWithData();

        $this->actingAs($admin)->get('/admin/years')->assertOk()
            ->assertSee(route('admin.years.show', $y2026), false)
            ->assertSee(route('admin.years.export', $y2026), false)
            ->assertSee('Consultar')
            ->assertSee('Respaldo');
    }

    public function test_the_blocked_write_message_points_to_the_admin_and_the_consult(): void
    {
        $message = (new ClosedAcademicYearException('Gestión 2026'))->getMessage();

        $this->assertStringContainsString('Solo el administrador puede reabrirla', $message);
        $this->assertStringContainsString('consultar y descargar', $message);
    }
}
