<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel Administrativo (rol «academic»): las funciones del director, salvo que no agrega
 * usuarios ni profesores, no toca cuentas de administrador o de director y solo consulta
 * las gestiones escolares.
 */
class SecretaryRoleTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'active'], $extra));
    }

    private function userPayload(array $override = []): array
    {
        return array_merge([
            'first_name' => 'Luis', 'last_name' => 'Rojas', 'second_last_name' => 'Vega',
            'email' => 'luis.rojas@school.bo', 'password' => 'Clave#12345', 'password_confirmation' => 'Clave#12345',
            'rol' => 'padre', 'telefono' => '70010011',
        ], $override);
    }

    private function student(): Student
    {
        $course = Course::firstOrCreate(['name' => '6° Primaria']);
        $parallel = Parallel::firstOrCreate(['name' => 'A', 'course_id' => $course->id], ['max_students' => 25]);

        return Student::create([
            'name' => 'Ana Paz', 'course' => '6° Primaria', 'course_id' => $course->id, 'parallel_id' => $parallel->id,
            'edad' => 11, 'genero' => 'Femenino', 'nivel' => 'intermedio', 'registration_date' => '2026-05-25', 'puntaje' => 80,
        ]);
    }

    // ------------------------------------------------------------ cargo

    public function test_academic_role_is_shown_as_secretary_of_the_direction(): void
    {
        $secretary = $this->user('academic');

        $this->assertTrue($secretary->isAdmin());
        $this->assertFalse($secretary->canCreateAccounts());
        $this->assertFalse($secretary->canManageYears());
        $this->assertTrue($secretary->seesTrimesterGradesOnly());
        $this->assertSame('Panel Administrativo', $secretary->roleLabel());

        $this->actingAs($secretary)->get('/dashboard')->assertOk()->assertSee('PANEL ADMINISTRATIVO');
    }

    public function test_admin_and_director_can_create_a_secretary_from_the_form(): void
    {
        $admin = $this->user('admin');
        $director = $this->user('director');

        foreach ([$admin, $director] as $i => $creator) {
            $this->actingAs($creator)->get('/users/create')->assertOk()->assertSee('Panel Administrativo');
            $this->actingAs($creator)->post('/users', $this->userPayload(['rol' => 'academic', 'email' => "sec{$i}@school.bo"]))
                ->assertRedirect(route('users.index'));
            $this->assertSame('academic', User::where('email', "sec{$i}@school.bo")->value('role'));
        }
    }

    // ------------------------------------------------------------ como el director

    public function test_secretary_reaches_the_director_sections(): void
    {
        $this->user('admin');
        $secretary = $this->user('academic');

        $this->actingAs($secretary)->get('/users')->assertOk();
        $this->actingAs($secretary)->get('/admin/audit')->assertOk();
        $this->actingAs($secretary)->get('/courses')->assertOk();
        $this->actingAs($secretary)->get('/tutors/create')->assertOk();
        $this->actingAs($secretary, 'sanctum')->getJson('/api/v1/app/admin/overview')->assertOk();
        $this->actingAs($secretary, 'sanctum')->getJson('/api/v1/app/admin/users')->assertOk();
        $this->actingAs($secretary, 'sanctum')->getJson('/api/v1/app/students')->assertOk();
    }

    public function test_secretary_manages_accounts_that_are_not_admin_or_director(): void
    {
        $this->user('admin');
        $secretary = $this->user('academic');
        $parent = $this->user('padre', ['email' => 'padre@school.bo']);
        $teacher = $this->user('profesor');

        $this->actingAs($secretary)->patch("/users/{$parent->id}/status")->assertSessionHas('success');
        $this->assertSame('inactive', $parent->fresh()->status);

        $this->actingAs($secretary)->put("/users/{$parent->id}", $this->userPayload([
            'email' => 'padre@school.bo', 'first_name' => 'Juan', 'rol' => 'madre', 'password' => null, 'password_confirmation' => null,
        ]))->assertRedirect(route('users.index'));
        $this->assertSame('madre', $parent->fresh()->role);

        $this->actingAs($secretary)->delete("/users/{$teacher->id}")->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $teacher->id]);
    }

    public function test_secretary_withdraws_students_like_the_director(): void
    {
        $this->user('admin');
        $student = $this->student();

        $this->actingAs($this->user('academic'))
            ->post(route('students.withdraw', $student), ['motive' => 'abandono', 'withdrawn_on' => now()->toDateString()])
            ->assertRedirect();

        $this->assertSame('inactivo', $student->fresh()->status);
    }

    public function test_secretary_sees_trimester_grades_instead_of_the_notebook(): void
    {
        $secretary = $this->user('academic');

        $this->actingAs($secretary)->get('/grades')->assertRedirect(route('grades.trimesters'));
        $this->actingAs($secretary)->get(route('grades.trimesters'))->assertOk();
        $this->actingAs($secretary)->get('/dashboard')->assertSee('href="' . route('grades.trimesters') . '"', false)
            ->assertDontSee('href="' . route('grades.index') . '"', false);
    }

    // ------------------------------------------------------------ no agrega usuarios

    public function test_secretary_cannot_add_users_on_the_web(): void
    {
        $this->user('admin');
        $secretary = $this->user('academic');

        $this->actingAs($secretary)->get('/users')->assertOk()->assertDontSee('href="' . route('users.create') . '"', false);
        $this->actingAs($secretary)->get('/users/create')->assertRedirect(route('users.index'))
            ->assertSessionHas('error', UserAdminService::SECRETARY_CREATE_MESSAGE);
        $this->actingAs($secretary)->post('/users', $this->userPayload(['email' => 'nuevo@school.bo']))
            ->assertSessionHas('error', UserAdminService::SECRETARY_CREATE_MESSAGE);

        $this->assertDatabaseMissing('users', ['email' => 'nuevo@school.bo']);
    }

    public function test_secretary_cannot_add_teachers(): void
    {
        $this->user('admin');
        $secretary = $this->user('academic');
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);

        $this->actingAs($secretary)->get('/teachers')->assertOk()->assertDontSee('href="' . route('teachers.create') . '"', false);
        $this->actingAs($secretary)->get('/teachers/create')->assertRedirect(route('teachers.index'));
        $this->actingAs($secretary)->post('/teachers', $this->userPayload([
            'email' => 'profe@school.bo', 'main_subject_id' => $subject->id,
        ]))->assertSessionHas('error', UserAdminService::SECRETARY_CREATE_MESSAGE);

        $this->assertDatabaseMissing('users', ['email' => 'profe@school.bo']);
    }

    public function test_secretary_cannot_add_users_from_the_app(): void
    {
        $this->user('admin');

        $this->actingAs($this->user('academic'), 'sanctum')->postJson('/api/v1/app/admin/users', [
            'first_name' => 'Ana', 'last_name' => 'Quispe', 'second_last_name' => 'Mamani',
            'email' => 'app@school.bo', 'password' => 'Clave#12345', 'rol' => 'padre',
        ])->assertStatus(422)->assertJsonPath('message', UserAdminService::SECRETARY_CREATE_MESSAGE);

        $this->assertDatabaseMissing('users', ['email' => 'app@school.bo']);
    }

    // ------------------------------------------------------------ cuentas protegidas

    public function test_secretary_cannot_touch_admin_or_director_accounts(): void
    {
        $admin = $this->user('admin');
        $director = $this->user('director');
        $secretary = $this->user('academic');

        foreach ([$admin, $director] as $target) {
            $this->actingAs($secretary)->delete("/users/{$target->id}")->assertSessionHas('error', UserAdminService::SECRETARY_ACCOUNT_MESSAGE);
            $this->actingAs($secretary)->patch("/users/{$target->id}/status")->assertSessionHas('error', UserAdminService::SECRETARY_ACCOUNT_MESSAGE);
            $this->actingAs($secretary)->get("/users/{$target->id}/edit")->assertRedirect(route('users.index'));
            $this->actingAs($secretary)->put("/users/{$target->id}", $this->userPayload([
                'email' => $target->email, 'rol' => 'padre', 'password' => null, 'password_confirmation' => null,
            ]))->assertSessionHas('error', UserAdminService::SECRETARY_ACCOUNT_MESSAGE);
            $this->actingAs($secretary, 'sanctum')->deleteJson("/api/v1/app/admin/users/{$target->id}")->assertStatus(422);

            $fresh = $target->fresh();
            $this->assertNotSame('padre', $fresh->role);
            $this->assertSame('active', $fresh->status);
            $this->assertNull($fresh->deleted_at);
        }

        $this->actingAs($secretary)->get('/users')->assertOk()->assertSee('Protegido')
            ->assertDontSee(route('users.edit', $admin->id), false)
            ->assertDontSee(route('users.edit', $director->id), false);
    }

    public function test_secretary_cannot_promote_anyone_to_admin_or_director(): void
    {
        $this->user('admin');
        $secretary = $this->user('academic');
        $parent = $this->user('padre', ['email' => 'padre@school.bo']);

        $this->actingAs($secretary)->get("/users/{$parent->id}/edit")->assertOk()
            ->assertDontSee('value="admin"', false)->assertDontSee('value="director"', false);

        foreach (['admin', 'director'] as $role) {
            $this->actingAs($secretary)->put("/users/{$parent->id}", $this->userPayload([
                'email' => 'padre@school.bo', 'rol' => $role, 'password' => null, 'password_confirmation' => null,
            ]))->assertSessionHas('error', UserAdminService::SECRETARY_ACCOUNT_MESSAGE);
        }

        $this->assertSame('padre', $parent->fresh()->role);
    }

    // ------------------------------------------------------------ gestiones: solo consulta

    public function test_secretary_only_consults_school_years(): void
    {
        $secretary = $this->user('academic');
        $year = AcademicYear::current();

        $this->actingAs($secretary)->get('/admin/years')->assertOk()
            ->assertSee('Solo consulta')
            ->assertDontSee('Crear una gestión')
            ->assertDontSee(route('admin.years.close', $year), false);
        $this->actingAs($secretary)->get("/admin/years/{$year->id}")->assertOk();
        $this->actingAs($secretary)->get("/admin/years/{$year->id}/export")->assertOk();

        $this->actingAs($secretary)->post('/admin/years', ['year' => 2030])->assertSessionHas('error');
        $this->actingAs($secretary, 'sanctum')->postJson('/api/v1/app/admin/years', ['year' => 2031])->assertStatus(422);
        $this->actingAs($secretary, 'sanctum')->postJson("/api/v1/app/admin/years/{$year->id}/close")->assertStatus(422);

        $this->assertSame(1, AcademicYear::count());
        $this->assertNull($year->fresh()->closed_at);
    }

    public function test_director_still_manages_school_years(): void
    {
        $director = $this->user('director');

        $this->actingAs($director)->get('/admin/years')->assertOk()->assertSee('Crear una gestión');
        $this->actingAs($director)->post('/admin/years', ['year' => 2030])->assertSessionHas('success');
        $this->assertSame(2, AcademicYear::count());
    }
}
