<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use App\Support\AuditPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bitácora de auditoría para el administrador: frases simples en lugar de JSON, Nro en lugar del
 * identificador del registro, y la fecha y hora al final.
 */
class AuditLogViewTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active', 'name' => 'Administrador Principal']);
    }

    private function log(string $action, string $entity, ?int $entityId = null, ?array $details = null, ?User $by = null, ?string $at = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $by?->id, 'action' => $action, 'entity' => $entity, 'entity_id' => $entityId,
            'ip_address' => '127.0.0.1', 'details' => $details, 'status' => 'success', 'created_at' => $at ?? now(),
        ]);
    }

    private function detail(AuditLog $log): string
    {
        return AuditPresenter::detail($log, AuditPresenter::names(collect([$log])));
    }

    public function test_the_table_has_nro_first_date_last_and_no_record_id(): void
    {
        $admin = $this->admin();
        $this->log('year.export', 'academic_year', 1, ['year' => 2026, 'closed' => false], $admin);

        $html = $this->actingAs($admin)->get('/admin/audit')->assertOk()->getContent();

        $this->assertStringNotContainsString('ID Registro', $html);
        $this->assertStringNotContainsString('Entidad</th>', $html);
        preg_match_all('/<th[^>]*>\s*([^<]+?)\s*<\/th>/', $html, $m);
        $this->assertSame(['Nro', 'Usuario', 'Acción', 'Módulo', 'Detalle', 'Dirección IP', 'Fecha y hora'], $m[1]);
    }

    public function test_details_are_simple_sentences_not_json(): void
    {
        $admin = $this->admin();
        $this->log('year.export', 'academic_year', 1, ['year' => 2026, 'closed' => false], $admin);
        $this->log('students.import', 'student', null, ['created' => 100, 'skipped' => 0, 'test_deleted' => 0]);

        $response = $this->actingAs($admin)->get('/admin/audit')->assertOk();

        $response->assertSee('Respaldo en Excel de la Gestión 2026 (abierta).')
            ->assertSee('Lista de estudiantes importada: 100 nuevos.')
            ->assertSee('Respaldo descargado')       // acción en castellano
            ->assertSee('Estudiantes importados')
            ->assertSee('Gestiones')                  // módulo en castellano
            ->assertDontSee('{&quot;year&quot;', false)
            ->assertDontSee('"closed"', false)
            ->assertDontSee('>academic_year<', false)       // el código técnico no se muestra como texto
            ->assertDontSee('>year.export<', false);
    }

    public function test_every_known_action_reads_as_a_clear_sentence_with_real_names(): void
    {
        $actor = $this->admin();
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active', 'name' => 'Rosa Quispe Lima']);
        $tutor = User::factory()->create(['role' => 'tutor', 'status' => 'active', 'name' => 'Juan Mamani Paz']);
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::create(['course_id' => $course->id, 'name' => 'A', 'max_students' => 40]);
        $student = Student::create(['name' => 'Alanes Cruz Raul', 'course' => 'Sexto', 'course_id' => $course->id, 'parallel_id' => $parallel->id, 'status' => 'activo']);
        $year = AcademicYear::current();

        $cases = [
            [$this->log('user.create', 'user', $teacher->id, ['role' => 'profesor']), 'Nuevo usuario: Rosa Quispe Lima (Profesor).'],
            [$this->log('user.update', 'user', $teacher->id, ['changed' => ['email', 'telefono', 'password']]), 'Se cambió el correo, el teléfono y la contraseña de Rosa Quispe Lima.'],
            [$this->log('user.update', 'user', $teacher->id, ['changed' => []]), 'Se guardaron los datos de Rosa Quispe Lima sin cambios.'],
            [$this->log('user.status', 'user', $teacher->id, ['from' => 'active', 'to' => 'inactive']), 'Rosa Quispe Lima: de Activo a Inactivo.'],
            [$this->log('user.delete', 'user', $teacher->id), 'Se eliminó a Rosa Quispe Lima. Se puede restaurar.'],
            [$this->log('user.restore', 'user', $teacher->id), 'Se restauró a Rosa Quispe Lima.'],
            [$this->log('user.force_delete', 'user', $teacher->id, ['email' => 'rosa@example.com']), 'Se eliminó definitivamente a Rosa Quispe Lima (rosa@example.com).'],
            [$this->log('year.create', 'academic_year', $year->id, ['year' => 2027]), 'Se creó la Gestión 2027.'],
            [$this->log('year.current', 'academic_year', $year->id, ['year' => 2027]), 'La Gestión 2027 ahora es la gestión actual.'],
            [$this->log('year.close', 'academic_year', $year->id, ['year' => 2026, 'next_current' => 2027]), 'Se cerró la Gestión 2026. La nueva gestión actual es la 2027.'],
            [$this->log('year.reopen', 'academic_year', $year->id, ['year' => 2026]), 'Se reabrió la Gestión 2026.'],
            [$this->log('year.export', 'academic_year', $year->id, ['year' => 2026, 'closed' => true]), 'Respaldo en Excel de la Gestión 2026 (cerrada).'],
            [$this->log('student_info_updated', 'student', $student->id, ['fields' => ['level', 'age']]), 'Se completó el nivel y la edad de Alanes Cruz Raul.'],
            [$this->log('guardian_assigned', 'student', $student->id, ['guardian_user_id' => $tutor->id, 'relation_type' => 'tutor', 'is_primary' => true, 'guardian_created' => true]), 'Juan Mamani Paz quedó como tutor principal de Alanes Cruz Raul. Se creó su cuenta.'],
            [$this->log('students.import', 'student', null, ['created' => 98, 'skipped' => 2, 'test_deleted' => 5]), 'Lista de estudiantes importada: 98 nuevos, 2 omitidos y 5 de prueba eliminados.'],
        ];

        foreach ($cases as [$log, $expected]) {
            $this->assertSame($expected, $this->detail($log), "acción {$log->action}");
        }
        unset($actor);
    }

    public function test_deleted_people_keep_their_name_in_the_detail(): void
    {
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active', 'name' => 'Rosa Quispe Lima']);
        $log = $this->log('user.delete', 'user', $teacher->id);
        $teacher->delete(); // eliminación recuperable

        $this->assertSame('Se eliminó a Rosa Quispe Lima. Se puede restaurar.', $this->detail($log));
    }

    public function test_unknown_actions_and_missing_records_do_not_break_the_page(): void
    {
        $admin = $this->admin();
        $this->log('algo.nuevo', 'cosas', null, ['clave_larga' => 'valor', 'activo' => true, 'lista' => ['a', 'b']]);
        $this->log('user.delete', 'user', 999999);   // el usuario ya no existe
        $this->log('year.close', 'academic_year', 1, null);

        $response = $this->actingAs($admin)->get('/admin/audit')->assertOk();

        $response->assertSee('clave larga: valor · activo: sí · lista: a, b')
            ->assertSee('Se eliminó a un usuario. Se puede restaurar.')
            ->assertSee('Se cerró la Gestión');
    }

    public function test_nro_follows_the_list_across_pages_newest_first(): void
    {
        $admin = $this->admin();
        for ($i = 1; $i <= 25; $i++) {
            $this->log('user.create', 'user', null, ['role' => 'padre'], $admin, now()->subMinutes(30 - $i)->toDateTimeString());
        }

        $first = $this->actingAs($admin)->get('/admin/audit')->assertOk()->getContent();
        $second = $this->actingAs($admin)->get('/admin/audit?page=2')->assertOk()->getContent();

        $numbers = fn (string $html) => array_map('intval', array_column(array_map(fn ($m) => [$m], $this->firstCells($html)), 0));
        $this->assertSame(range(1, 20), $numbers($first));
        $this->assertSame(range(21, 25), $numbers($second), 'la segunda página sigue la numeración');
    }

    /** Texto de la primera celda (Nro) de cada fila de la tabla. */
    private function firstCells(string $html): array
    {
        preg_match_all('/<tr>\s*<td class="font-weight-bold text-muted">\s*(\d+)\s*<\/td>/', $html, $m);

        return $m[1];
    }

    public function test_the_newest_record_comes_first_and_the_date_is_in_the_last_column(): void
    {
        $admin = $this->admin();
        $this->log('year.reopen', 'academic_year', 1, ['year' => 2025], $admin, '2026-03-01 08:00:00');
        $this->log('year.close', 'academic_year', 1, ['year' => 2026], $admin, '2026-10-26 09:44:18');

        $html = $this->actingAs($admin)->get('/admin/audit')->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'Se reabrió la Gestión 2025'), strpos($html, 'Se cerró la Gestión 2026'), 'lo más reciente va primero');
        $this->assertMatchesRegularExpression('/<td class="text-right text-nowrap">\s*<span class="d-block font-weight-bold">26\/10\/2026<\/span>\s*<small class="text-muted">09:44:18<\/small>\s*<\/td>\s*<\/tr>/', $html);
    }

    public function test_the_filters_use_simple_names_and_ignore_letter_case_in_the_module(): void
    {
        $admin = $this->admin();
        $this->log('student_info_updated', 'Student', null, ['fields' => ['level']]);   // mayúscula antigua
        $this->log('students.import', 'student', null, ['created' => 1]);
        $this->log('year.export', 'academic_year', 1, ['year' => 2026]);

        $page = $this->actingAs($admin)->get('/admin/audit')->assertOk();
        $page->assertSee('Todos los módulos')
            ->assertSee('<option value="student"', false)
            ->assertSee('>Estudiantes</option>', false)
            ->assertSee('>Respaldo descargado</option>', false)
            ->assertDontSee('<option value="Student"', false);   // no aparece duplicado

        // Filtrar por «student» trae las dos (con y sin mayúscula) y ninguna de otro módulo.
        $filtered = $this->actingAs($admin)->get('/admin/audit?entity=student')->assertOk();
        $filtered->assertSee('Datos completados')->assertSee('Estudiantes importados')->assertDontSee('Respaldo descargado</span>', false);

        $this->actingAs($admin)->get('/admin/audit?action=year.export')->assertOk()
            ->assertSee('Respaldo descargado')->assertDontSee('Datos completados</span>', false);
    }

    public function test_a_filter_without_matches_says_so(): void
    {
        $admin = $this->admin();
        $this->log('year.export', 'academic_year', 1, ['year' => 2026]);

        $this->actingAs($admin)->get('/admin/audit?entity=user&action=year.export')->assertOk()
            ->assertSee('Ningún registro coincide con los filtros elegidos');
    }

    public function test_only_the_admin_can_open_the_audit_log(): void
    {
        foreach (['academic', 'profesor', 'padre'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']))->get('/admin/audit')->assertRedirect('/');
        }
    }
}
