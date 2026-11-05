<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rol director: los permisos del administrador, salvo las cuentas de administrador
 * (no las crea, ni las modifica, suspende, elimina o restaura) y, en lugar del
 * Cuaderno Pedagógico, ve solo las notas finales de cada trimestre.
 */
class DirectorRoleTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN_MESSAGE = 'El director no puede crear, modificar, suspender ni eliminar administradores.';

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

    // ------------------------------------------------------------ rol

    public function test_director_is_treated_as_admin(): void
    {
        $director = $this->user('director');

        $this->assertTrue($director->isAdmin());
        $this->assertTrue($director->isDirector());
        $this->assertFalse($director->isMainAdmin());
    }

    public function test_main_admin_is_the_first_admin_account(): void
    {
        $principal = $this->user('admin');
        $second = $this->user('admin');

        $this->assertTrue($principal->isMainAdmin());
        $this->assertFalse($second->isMainAdmin());
        $this->assertSame($principal->id, User::mainAdminId());
    }

    public function test_director_reaches_admin_web_sections(): void
    {
        $this->user('admin');
        $director = $this->user('director');

        $this->actingAs($director)->get('/users')->assertOk();
        $this->actingAs($director)->get('/admin/audit')->assertOk();
        $this->actingAs($director)->get('/admin/years')->assertOk();
        $this->actingAs($director)->get('/courses')->assertOk();
    }

    public function test_director_reaches_admin_api_of_the_app(): void
    {
        $this->user('admin');
        $director = $this->user('director');

        $this->actingAs($director, 'sanctum')->getJson('/api/v1/app/admin/overview')->assertOk();
        $this->actingAs($director, 'sanctum')->getJson('/api/v1/app/admin/users')->assertOk();
    }

    // ------------------------------------------------------------ cuentas

    public function test_user_form_offers_director_but_not_admin_to_the_director(): void
    {
        $this->user('admin');
        $director = $this->user('director');

        $this->actingAs($director)->get('/users/create')->assertOk()
            ->assertSee('value="director"', false)
            ->assertDontSee('value="admin"', false)
            ->assertDontSee('value="estudiante"', false);
    }

    public function test_admin_can_create_a_director_and_an_admin(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/users/create')->assertOk()
            ->assertSee('value="admin"', false)->assertSee('value="director"', false);

        $this->actingAs($admin)->post('/users', $this->userPayload(['rol' => 'director', 'email' => 'dir@school.bo']))
            ->assertRedirect(route('users.index'));
        $this->actingAs($admin)->post('/users', $this->userPayload(['rol' => 'admin', 'email' => 'adm@school.bo']))
            ->assertRedirect(route('users.index'));

        $this->assertSame('director', User::where('email', 'dir@school.bo')->value('role'));
        $this->assertSame('admin', User::where('email', 'adm@school.bo')->value('role'));
    }

    public function test_director_cannot_create_an_admin_on_the_web_or_the_app(): void
    {
        $this->user('admin');
        $director = $this->user('director');

        $this->actingAs($director)->post('/users', $this->userPayload(['rol' => 'admin', 'email' => 'web@school.bo']))
            ->assertSessionHas('error', self::ADMIN_MESSAGE);
        $this->actingAs($director, 'sanctum')->postJson('/api/v1/app/admin/users', [
            'first_name' => 'Ana', 'last_name' => 'Quispe', 'second_last_name' => 'Mamani',
            'email' => 'app@school.bo', 'password' => 'Clave#12345', 'rol' => 'admin',
        ])->assertStatus(422)->assertJsonPath('message', self::ADMIN_MESSAGE);

        $this->assertDatabaseMissing('users', ['email' => 'web@school.bo']);
        $this->assertDatabaseMissing('users', ['email' => 'app@school.bo']);

        // Otros roles sí.
        $this->actingAs($director)->post('/users', $this->userPayload(['rol' => 'profesor', 'email' => 'prof@school.bo']))
            ->assertRedirect(route('users.index'));
        $this->assertSame('profesor', User::where('email', 'prof@school.bo')->value('role'));
    }

    public function test_director_cannot_promote_a_user_to_admin(): void
    {
        $this->user('admin');
        $director = $this->user('director');
        $parent = $this->user('padre', ['email' => 'padre@school.bo']);

        $this->actingAs($director)->put("/users/{$parent->id}", $this->userPayload([
            'email' => 'padre@school.bo', 'rol' => 'admin', 'password' => null, 'password_confirmation' => null,
        ]))->assertSessionHas('error', self::ADMIN_MESSAGE);

        $this->assertSame('padre', $parent->fresh()->role);
    }

    public function test_director_manages_users_that_are_not_admins(): void
    {
        $this->user('admin');
        $director = $this->user('director');
        $parent = $this->user('padre');
        $teacher = $this->user('profesor');

        $this->actingAs($director)->patch("/users/{$parent->id}/status")->assertSessionHas('success');
        $this->assertSame('inactive', $parent->fresh()->status);

        $this->actingAs($director)->delete("/users/{$teacher->id}")->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $teacher->id]);
    }

    public function test_director_cannot_touch_any_admin_account_on_the_web(): void
    {
        $principal = $this->user('admin');
        $otherAdmin = $this->user('admin');
        $director = $this->user('director');

        foreach ([$principal, $otherAdmin] as $admin) {
            $this->actingAs($director)->delete("/users/{$admin->id}")->assertSessionHas('error', self::ADMIN_MESSAGE);
            $this->actingAs($director)->delete("/users/{$admin->id}/force")->assertSessionHas('error', self::ADMIN_MESSAGE);
            $this->actingAs($director)->patch("/users/{$admin->id}/status")->assertSessionHas('error', self::ADMIN_MESSAGE);
            $this->actingAs($director)->get("/users/{$admin->id}/edit")->assertRedirect(route('users.index'));
            $this->actingAs($director)->put("/users/{$admin->id}", $this->userPayload([
                'email' => $admin->email, 'rol' => 'padre', 'password' => null, 'password_confirmation' => null,
            ]))->assertSessionHas('error', self::ADMIN_MESSAGE);

            $fresh = $admin->fresh();
            $this->assertSame('admin', $fresh->role);
            $this->assertSame('active', $fresh->status);
            $this->assertNull($fresh->deleted_at);
        }
    }

    public function test_director_cannot_restore_a_deleted_admin(): void
    {
        $principal = $this->user('admin');
        $deletedAdmin = $this->user('admin');
        $director = $this->user('director');
        $this->actingAs($principal)->delete("/users/{$deletedAdmin->id}");
        $this->assertSoftDeleted('users', ['id' => $deletedAdmin->id]);

        $this->actingAs($director)->patch("/users/{$deletedAdmin->id}/restore")->assertSessionHas('error', self::ADMIN_MESSAGE);
        $this->actingAs($director, 'sanctum')->postJson("/api/v1/app/admin/users/{$deletedAdmin->id}/restore")->assertStatus(422);

        $this->assertSoftDeleted('users', ['id' => $deletedAdmin->id]);
    }

    public function test_director_cannot_touch_admins_from_the_app(): void
    {
        $principal = $this->user('admin');
        $director = $this->user('director');

        $this->actingAs($director, 'sanctum')->deleteJson("/api/v1/app/admin/users/{$principal->id}")->assertStatus(422);
        $this->actingAs($director, 'sanctum')->patchJson("/api/v1/app/admin/users/{$principal->id}/status")->assertStatus(422);
        $this->actingAs($director, 'sanctum')->putJson("/api/v1/app/admin/users/{$principal->id}", [
            'first_name' => 'Otro', 'last_name' => 'Nombre', 'second_last_name' => 'Cualquiera',
            'email' => $principal->email, 'rol' => 'padre',
        ])->assertStatus(422);

        $fresh = $principal->fresh();
        $this->assertSame('admin', $fresh->role);
        $this->assertSame('active', $fresh->status);
        $this->assertNull($fresh->deleted_at);
    }

    public function test_user_list_protects_admin_rows_only_for_the_director(): void
    {
        $principal = $this->user('admin');
        $otherAdmin = $this->user('admin');
        $director = $this->user('director');

        $this->actingAs($director)->get('/users')->assertOk()
            ->assertSee('Protegido')
            ->assertDontSee(route('users.edit', $principal->id), false)
            ->assertDontSee(route('users.edit', $otherAdmin->id), false);

        $this->actingAs($principal)->get('/users')->assertOk()
            ->assertSee(route('users.edit', $director->id), false)
            ->assertSee(route('users.edit', $otherAdmin->id), false)
            ->assertDontSee('Protegido');
    }

    public function test_main_admin_keeps_full_control_over_the_director(): void
    {
        $principal = $this->user('admin');
        $director = $this->user('director');

        $this->actingAs($principal)->patch("/users/{$director->id}/status")->assertSessionHas('success');
        $this->assertSame('inactive', $director->fresh()->status);

        $this->actingAs($principal)->delete("/users/{$director->id}")->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $director->id]);
    }

    // ------------------------------------------------------------ notas trimestrales

    /** @return array{0: Subject, 1: Parallel, 2: Student} */
    private function classWithStudent(): array
    {
        $course = Course::firstOrCreate(['name' => '6° Primaria']);
        $parallel = Parallel::firstOrCreate(['name' => 'A', 'course_id' => $course->id], ['max_students' => 25]);
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        $student = Student::create([
            'name' => 'Ana Paz', 'course' => '6° Primaria', 'course_id' => $course->id, 'parallel_id' => $parallel->id,
            'edad' => 11, 'genero' => 'Femenino', 'nivel' => 'intermedio', 'registration_date' => '2026-05-25', 'puntaje' => 80,
        ]);

        return [$subject, $parallel, $student];
    }

    private function grade(Student $student, Subject $subject, int $quarter, string $type, int $number, float $score): void
    {
        Grade::create([
            'student_id' => $student->id, 'subject_id' => $subject->id, 'score' => $score, 'type' => $type,
            'status' => 'completed', 'quarter' => $quarter, 'activity_number' => $number,
            'is_robot_activity' => $type === 'robot',
        ]);
    }

    public function test_director_is_sent_from_the_notebook_to_the_trimester_grades(): void
    {
        $director = $this->user('director');

        $this->actingAs($director)->get('/grades?subject_id=1&parallel_id=2')
            ->assertRedirect(route('grades.trimesters', ['subject_id' => 1, 'parallel_id' => 2]));
        $this->actingAs($director)->get('/grades/export-pdf?subject_id=1&parallel_id=2')
            ->assertRedirect(route('grades.trimesters', ['subject_id' => 1, 'parallel_id' => 2]));

        $this->actingAs($director)->get('/dashboard')->assertOk()
            ->assertSee('href="' . route('grades.trimesters') . '"', false)
            ->assertDontSee('href="' . route('grades.index') . '"', false);
    }

    public function test_trimester_grades_use_the_notebook_formula(): void
    {
        [$subject, $parallel, $student] = $this->classWithStudent();
        $otherSubject = Subject::firstOrCreate(['code' => 'MAT'], ['name' => 'Matemática']);

        // 1.er trimestre: SER (80, 90, 100) → 9; SABER 60 → 27; HACER 100 → 40; autoevaluación 5 → 81.
        $this->grade($student, $subject, 1, 'activity', 1, 80);
        $this->grade($student, $subject, 1, 'activity', 2, 90);
        $this->grade($student, $subject, 1, 'activity', 3, 100);
        $this->grade($student, $subject, 1, 'exam', 1, 60);
        $this->grade($student, $subject, 1, 'robot', 1, 100);
        $this->grade($student, $subject, 1, 'autoeval', 1, 5);
        // 2.º trimestre: solo SABER 100 → 45.
        $this->grade($student, $subject, 2, 'exam', 1, 100);
        // 3.er trimestre: solo hay notas de otra asignatura → sin nota.
        $this->grade($student, $otherSubject, 3, 'exam', 1, 100);

        $this->actingAs($this->user('director'))
            ->get(route('grades.trimesters', ['subject_id' => $subject->id, 'parallel_id' => $parallel->id]))
            ->assertOk()
            ->assertSee('Ana Paz')
            ->assertDontSee('Ser 1')
            ->assertViewHas('rows', function ($rows) use ($student) {
                $row = $rows->first();

                return $rows->count() === 1
                    && $row['student']->is($student)
                    && $row['quarters'] === [1 => 81.0, 2 => 45.0, 3 => null]
                    && $row['annual'] === 63.0;
            });
    }

    public function test_trimester_grades_are_only_for_the_director_and_the_secretary(): void
    {
        foreach (['admin', 'profesor', 'padre'] as $role) {
            $this->actingAs($this->user($role))->get(route('grades.trimesters'))->assertRedirect('/');
        }

        foreach (['director', 'academic'] as $role) {
            $this->actingAs($this->user($role))->get(route('grades.trimesters'))->assertOk()
                ->assertSee('Seleccione una asignatura y un paralelo.');
        }
    }

    public function test_admin_still_sees_the_full_notebook(): void
    {
        $this->actingAs($this->user('admin'))->get('/grades')->assertOk()->assertDontSee('Notas trimestrales');
    }
}
