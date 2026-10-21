<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\RobotActivity;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\AcademicYearService;
use App\Services\ClosedAcademicYearException;
use App\Services\UserAdminException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Crear, cambiar, cerrar y reabrir gestiones. Solo el rol admin (director y administrador). */
class AcademicYearManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function service(): AcademicYearService
    {
        return app(AcademicYearService::class);
    }

    private function student(): Student
    {
        $course = Course::firstOrCreate(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::firstOrCreate(['course_id' => $course->id, 'name' => 'A'], ['max_students' => 40]);

        return Student::create(['name' => 'Ana Uno', 'course' => $course->name, 'course_id' => $course->id, 'parallel_id' => $parallel->id, 'status' => 'activo']);
    }

    private function gradeIn(AcademicYear $year, Student $student): Grade
    {
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);

        return Grade::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'score' => 70, 'type' => 'activity',
            'status' => 'completed', 'quarter' => 1, 'academic_year_id' => $year->id,
        ]);
    }

    // ------------------------------------------------------------ crear y cambiar

    public function test_admin_creates_a_year_that_is_not_current_and_it_is_audited(): void
    {
        $admin = $this->user('admin');

        $year = $this->service()->create($admin, 2027);

        $this->assertSame('Gestión 2027', $year->name);
        $this->assertFalse($year->is_current);
        $this->assertTrue(AuditLog::where('action', 'year.create')->where('entity_id', $year->id)->where('user_id', $admin->id)->exists());
        $this->expectException(\InvalidArgumentException::class);
        $this->service()->create($admin, 2027);
    }

    public function test_set_current_leaves_exactly_one_current_year(): void
    {
        $admin = $this->user('admin');
        $y2027 = $this->service()->create($admin, 2027);

        $this->service()->setCurrent($admin, $y2027);

        $this->assertSame(1, AcademicYear::where('is_current', true)->count());
        $this->assertSame(2027, AcademicYear::current()->year);
        $this->assertTrue(AuditLog::where('action', 'year.current')->exists());
    }

    public function test_new_records_follow_the_current_year_and_old_year_stays_readable(): void
    {
        $admin = $this->user('admin');
        $student = $this->student();
        $y2026 = AcademicYear::current();
        $this->gradeIn($y2026, $student);
        $y2027 = $this->service()->create($admin, 2027);

        $this->service()->setCurrent($admin, $y2027);
        $subject = Subject::first();
        $new = Grade::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'score' => 90, 'type' => 'activity', 'status' => 'completed', 'quarter' => 1]);

        $this->assertSame($y2027->id, $new->fresh()->academic_year_id);
        $this->assertSame([90.0], Grade::pluck('score')->all(), 'por defecto solo se ve la gestión actual');
        $this->assertSame(2, Grade::withoutGlobalScopes()->count(), 'la gestión anterior no se pierde');
    }

    // ------------------------------------------------------------ cerrar y reabrir

    public function test_closing_a_year_blocks_every_write_in_it(): void
    {
        $admin = $this->user('admin');
        $student = $this->student();
        $y2026 = AcademicYear::current();
        $grade = $this->gradeIn($y2026, $student);
        $attendance = Attendance::create(['student_id' => $student->id, 'subject_id' => $grade->subject_id, 'date' => '2026-10-01', 'status' => 'presente']);
        $y2027 = $this->service()->create($admin, 2027);

        $this->service()->close($admin, $y2026, $y2027);

        foreach ([
            'crear nota' => fn () => $this->gradeIn($y2026, $student),
            'editar nota' => fn () => Grade::withoutGlobalScopes()->find($grade->id)->update(['score' => 10]),
            'borrar nota' => fn () => Grade::withoutGlobalScopes()->find($grade->id)->delete(),
            'editar asistencia' => fn () => Attendance::withoutGlobalScopes()->find($attendance->id)->update(['status' => 'falta']),
            'crear actividad' => fn () => RobotActivity::create([
                'id' => (string) Str::uuid(), 'teacher_user_id' => $this->user('profesor')->id, 'parallel_id' => $student->parallel_id,
                'subject_id' => $grade->subject_id, 'quarter' => 1, 'activity_number' => 1, 'status' => 'pending',
                'academic_year_id' => $y2026->id, 'payload' => [],
            ]),
        ] as $what => $action) {
            try {
                $action();
                $this->fail("se pudo $what en una gestión cerrada");
            } catch (ClosedAcademicYearException) {
                // esperado
            }
        }

        $this->assertEquals(70, Grade::withoutGlobalScopes()->find($grade->id)->score);
        $this->assertTrue(AuditLog::where('action', 'year.close')->where('user_id', $admin->id)->exists());
    }

    public function test_closing_the_current_year_requires_a_successor_and_makes_it_current(): void
    {
        $admin = $this->user('admin');
        $y2026 = AcademicYear::current();
        $y2027 = $this->service()->create($admin, 2027);

        try {
            $this->service()->close($admin, $y2026);
            $this->fail('se cerró la gestión actual sin indicar la siguiente');
        } catch (UserAdminException) {
        }
        $this->assertNull($y2026->fresh()->closed_at);

        $this->service()->close($admin, $y2026, $y2027);

        $this->assertNotNull($y2026->fresh()->closed_at);
        $this->assertSame($admin->id, $y2026->fresh()->closed_by);
        $this->assertSame(2027, AcademicYear::current()->year);
        $this->assertSame(1, AcademicYear::where('is_current', true)->count());
    }

    public function test_cannot_make_a_closed_year_current_and_cannot_close_a_year_twice(): void
    {
        $admin = $this->user('admin');
        $y2026 = AcademicYear::current();
        $y2027 = $this->service()->create($admin, 2027);
        $this->service()->close($admin, $y2026, $y2027);

        $rejected = 0;
        foreach ([fn () => $this->service()->setCurrent($admin, $y2026), fn () => $this->service()->close($admin, $y2026)] as $action) {
            try {
                $action();
            } catch (UserAdminException) {
                $rejected++;
            }
        }

        $this->assertSame(2, $rejected, 'ambas operaciones debían rechazarse');
        $this->assertSame(2027, AcademicYear::current()->year);
    }

    public function test_reopening_allows_writes_again_and_is_audited(): void
    {
        $admin = $this->user('admin');
        $student = $this->student();
        $y2026 = AcademicYear::current();
        $grade = $this->gradeIn($y2026, $student);
        $y2027 = $this->service()->create($admin, 2027);
        $this->service()->close($admin, $y2026, $y2027);

        $this->service()->reopen($admin, $y2026->fresh());

        $this->actingAs($admin); // el historial de notas guarda quién cambió la nota
        Grade::withoutGlobalScopes()->find($grade->id)->update(['score' => 75]);
        $this->assertEquals(75, Grade::withoutGlobalScopes()->find($grade->id)->score);
        $this->assertNull($y2026->fresh()->closed_at);
        $this->assertTrue(AuditLog::where('action', 'year.reopen')->exists());
    }

    // ------------------------------------------------------------ respuesta amable, no un error 500

    public function test_a_blocked_write_answers_with_a_clear_message(): void
    {
        $admin = $this->user('admin');
        $student = $this->student();
        $y2026 = AcademicYear::current();
        $y2027 = $this->service()->create($admin, 2027);
        $this->service()->close($admin, $y2026, $y2027);
        Route::middleware('web')->post('/__cierre_prueba', fn () => $this->gradeIn($y2026, $student));

        $json = $this->postJson('/__cierre_prueba')->assertStatus(409);
        $this->assertStringContainsString('cerrada', $json->json('message'));

        $this->from('/atras')->post('/__cierre_prueba')->assertRedirect('/atras')->assertSessionHas('error');
    }

    // ------------------------------------------------------------ permisos

    public function test_only_admin_manages_years_on_the_api(): void
    {
        $y2026 = AcademicYear::current();
        foreach (['academic', 'profesor', 'padre', 'estudiante', 'robot'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/app/admin/years', ['year' => 2027])->assertForbidden();
            $this->actingAs($user, 'sanctum')->postJson("/api/v1/app/admin/years/{$y2026->id}/reopen")->assertForbidden();
            $this->actingAs($user, 'sanctum')->postJson("/api/v1/app/admin/years/{$y2026->id}/close")->assertForbidden();
            $this->actingAs($user, 'sanctum')->postJson("/api/v1/app/admin/years/{$y2026->id}/current")->assertForbidden();
        }
        $this->assertNull($y2026->fresh()->closed_at);
        $this->assertSame(1, AcademicYear::count());
    }

    public function test_admin_manages_years_through_the_api(): void
    {
        $admin = $this->user('admin');
        $y2026 = AcademicYear::current();

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/app/admin/years', ['year' => 2027])->assertCreated()->json('data');
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/app/admin/years', ['year' => 2027])->assertStatus(422);
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/app/admin/years/{$y2026->id}/close")->assertStatus(422);
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/app/admin/years/{$y2026->id}/close", ['next_year_id' => $created['id']])->assertOk()
            ->assertJsonPath('data.closed', true);
        $list = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/years')->json('data');

        $this->assertSame([2027, 2026], collect($list)->pluck('year')->all());
        $this->assertSame([true, false], collect($list)->pluck('is_current')->all());
        $this->assertSame([false, true], collect($list)->pluck('closed')->all());
        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/app/admin/years/{$y2026->id}/reopen")->assertOk()->assertJsonPath('data.closed', false);
    }

    public function test_only_admin_reaches_the_web_screen(): void
    {
        $this->actingAs($this->user('admin'))->get('/admin/years')->assertOk()->assertSee('Gestión 2026');
        foreach (['academic', 'profesor', 'padre'] as $role) {
            $this->actingAs($this->user($role))->get('/admin/years')->assertRedirect('/');
        }
        $y = AcademicYear::current();
        $this->actingAs($this->user('academic'))->post("/admin/years/{$y->id}/reopen");
        $this->assertNull($y->fresh()->closed_at);
    }

    public function test_web_screen_creates_sets_current_closes_and_reopens(): void
    {
        $admin = $this->user('admin');
        $y2026 = AcademicYear::current();

        $this->actingAs($admin)->post('/admin/years', ['year' => 2027])->assertRedirect('/admin/years');
        $y2027 = AcademicYear::where('year', 2027)->firstOrFail();
        $this->actingAs($admin)->post("/admin/years/{$y2026->id}/close", ['next_year_id' => $y2027->id])->assertRedirect('/admin/years');
        $this->assertNotNull($y2026->fresh()->closed_at);
        $this->assertTrue($y2027->fresh()->is_current);

        $this->actingAs($admin)->post("/admin/years/{$y2026->id}/reopen")->assertRedirect('/admin/years');
        $this->actingAs($admin)->post("/admin/years/{$y2026->id}/current")->assertRedirect('/admin/years');
        $this->assertTrue($y2026->fresh()->is_current);
        $this->assertSame(1, AcademicYear::where('is_current', true)->count());
    }

    // ------------------------------------------------------------ asignaciones por gestión

    public function test_teaching_assignments_belong_to_a_year_and_the_same_subject_can_repeat_next_year(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('profesor');
        $student = $this->student();
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);

        TeachingAssignment::create(['teacher_user_id' => $teacher->id, 'subject_id' => $subject->id, 'parallel_id' => $student->parallel_id]);
        $y2027 = $this->service()->create($admin, 2027);
        $this->service()->setCurrent($admin, $y2027);

        $this->assertSame(0, TeachingAssignment::count(), 'la gestión nueva empieza sin asignaciones');
        TeachingAssignment::create(['teacher_user_id' => $this->user('profesor')->id, 'subject_id' => $subject->id, 'parallel_id' => $student->parallel_id]);
        $this->assertSame(1, TeachingAssignment::count());
        $this->assertSame(2, TeachingAssignment::withoutGlobalScopes()->count());
    }

    // ------------------------------------------------------------ comandos

    public function test_commands_list_create_and_set_current(): void
    {
        $this->artisan('years:create', ['year' => 2027])->assertSuccessful();
        $this->artisan('years:list')->assertSuccessful();
        $this->artisan('years:set-current', ['year' => 2027])->assertSuccessful();
        $this->assertSame(2027, AcademicYear::current()->year);
        $this->artisan('years:create', ['year' => 2027])->assertFailed();
    }

    public function test_close_command_needs_explicit_confirmation(): void
    {
        $this->artisan('years:create', ['year' => 2027])->assertSuccessful();

        $this->artisan('years:close', ['year' => 2026, '--next' => 2027])->assertFailed();
        $this->assertNull(AcademicYear::where('year', 2026)->first()->closed_at, 'sin --yes no se cierra nada');

        $this->artisan('years:close', ['year' => 2026, '--next' => 2027, '--yes' => true])->assertSuccessful();
        $this->assertNotNull(AcademicYear::where('year', 2026)->first()->closed_at);
        $this->artisan('years:reopen', ['year' => 2026, '--yes' => true])->assertSuccessful();
        $this->assertNull(AcademicYear::where('year', 2026)->first()->closed_at);
    }
}
