<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Ventana "Editar información" de la ficha del estudiante: asigna un responsable
 * cuando el estudiante se registró sin tutor (elegido o creado en el momento).
 */
class StudentGuardianTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Clave#12345';

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function student(array $extra = []): Student
    {
        $course = Course::create(['name' => 'Sexto de Primaria', 'nivel_educativo' => 'primaria']);
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 30]);

        return Student::create(array_merge([
            'name' => 'Aduviri Ayca Victor', 'course' => $course->name, 'course_id' => $course->id,
            'parallel_id' => $parallel->id, 'status' => 'activo',
        ], $extra));
    }

    private function guardianUser(string $role = 'tutor', array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'active'], $extra));
    }

    private function newGuardianPayload(array $override = []): array
    {
        return array_merge([
            'mode' => 'new', 'ci' => '7654321', 'last_name' => 'Mamani', 'second_last_name' => 'Choque', 'first_name' => 'Rosa',
            'phone' => '70010011', 'email' => 'rosa.mamani@example.com', 'relation_type' => 'madre',
            'password' => self::STRONG, 'password_confirmation' => self::STRONG, 'is_primary' => '1',
        ], $override);
    }

    public function test_the_edit_button_and_modal_are_shown_to_admin_and_academic_only(): void
    {
        $student = $this->student();

        foreach (['admin', 'academic'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']))
                ->get("/grades/{$student->id}")->assertOk()
                ->assertSee('Editar información')
                ->assertSee('id="guardianModal"', false)
                ->assertSee('Añadir responsable')
                ->assertSee('no tiene responsable registrado');
        }

        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $student->update(['teacher_user_id' => $teacher->id]);
        $this->actingAs($teacher)->get("/grades/{$student->id}")->assertOk()
            ->assertDontSee('Editar información')
            ->assertDontSee('guardianModal');
    }

    public function test_it_assigns_an_existing_guardian_as_primary(): void
    {
        $student = $this->student();
        $tutor = $this->guardianUser('tutor');

        $this->actingAs($this->admin())->post("/students/{$student->id}/guardian", [
            'mode' => 'existing', 'user_id' => $tutor->id, 'relation_type' => 'tutor', 'is_primary' => '1',
        ])->assertRedirect(route('grades.show', $student))->assertSessionHas('success');

        $this->assertSame($tutor->id, (int) $student->fresh()->parent_user_id);
        $this->assertDatabaseHas('student_family_members', [
            'student_id' => $student->id, 'user_id' => $tutor->id, 'relation_type' => 'tutor', 'is_primary' => true, 'active' => true,
        ]);
        $this->assertTrue(AuditLog::where('action', 'guardian_assigned')->where('entity_id', $student->id)->exists());

        $this->actingAs($this->admin())->get("/grades/{$student->id}")
            ->assertSee($tutor->name)
            ->assertDontSee('Añadir responsable');
    }

    public function test_it_creates_a_new_guardian_and_links_it(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())->post("/students/{$student->id}/guardian", $this->newGuardianPayload())
            ->assertRedirect(route('grades.show', $student));

        $guardian = User::where('email', 'rosa.mamani@example.com')->firstOrFail();
        $this->assertSame('madre', $guardian->role);
        $this->assertSame('active', $guardian->status);
        $this->assertSame('Mamani Choque Rosa', $guardian->name);
        $this->assertTrue(Hash::check(self::STRONG, $guardian->password));
        $this->assertSame($guardian->id, (int) $student->fresh()->parent_user_id);
        $this->assertDatabaseHas('student_family_members', ['student_id' => $student->id, 'user_id' => $guardian->id, 'is_primary' => true]);
    }

    public function test_a_new_guardian_needs_a_matching_confirmation_and_unique_data(): void
    {
        $student = $this->student();
        $admin = $this->admin();
        $this->guardianUser('padre', ['email' => 'rosa.mamani@example.com', 'ci' => '7654321']);

        $this->actingAs($admin)->from("/grades/{$student->id}")
            ->post("/students/{$student->id}/guardian", $this->newGuardianPayload(['password_confirmation' => 'Otra#Clave99', 'email' => 'otra@example.com', 'ci' => '1111111']))
            ->assertRedirect("/grades/{$student->id}")
            ->assertSessionHasErrorsIn('guardian', ['password']);

        $this->actingAs($admin)->post("/students/{$student->id}/guardian", $this->newGuardianPayload())
            ->assertSessionHasErrorsIn('guardian', ['email', 'ci']);

        $this->assertNull($student->fresh()->parent_user_id);
        $this->assertDatabaseMissing('users', ['email' => 'otra@example.com']);
    }

    public function test_an_existing_guardian_must_be_an_active_family_user(): void
    {
        $student = $this->student();
        $admin = $this->admin();

        foreach ([$this->guardianUser('profesor'), $this->guardianUser('padre', ['status' => 'inactive'])] as $invalid) {
            $this->actingAs($admin)->post("/students/{$student->id}/guardian", [
                'mode' => 'existing', 'user_id' => $invalid->id, 'relation_type' => 'tutor',
            ])->assertSessionHasErrorsIn('guardian', ['user_id']);
        }

        $this->assertNull($student->fresh()->parent_user_id);
        $this->assertSame(0, StudentFamilyMember::where('student_id', $student->id)->count());
    }

    public function test_a_secondary_guardian_does_not_replace_the_primary_one(): void
    {
        $primary = $this->guardianUser('padre');
        $second = $this->guardianUser('madre');
        $student = $this->student(['parent_user_id' => $primary->id]);
        StudentFamilyMember::create(['student_id' => $student->id, 'user_id' => $primary->id, 'relation_type' => 'padre', 'is_primary' => true]);

        $this->actingAs($this->admin())->post("/students/{$student->id}/guardian", [
            'mode' => 'existing', 'user_id' => $second->id, 'relation_type' => 'madre', 'is_primary' => '0',
        ])->assertRedirect();

        $this->assertSame($primary->id, (int) $student->fresh()->parent_user_id);
        $this->assertDatabaseHas('student_family_members', ['student_id' => $student->id, 'user_id' => $primary->id, 'is_primary' => true]);
        $this->assertDatabaseHas('student_family_members', ['student_id' => $student->id, 'user_id' => $second->id, 'is_primary' => false]);
    }

    public function test_only_admin_and_academic_can_assign_guardians(): void
    {
        $student = $this->student();
        $tutor = $this->guardianUser('tutor');
        $payload = ['mode' => 'existing', 'user_id' => $tutor->id, 'relation_type' => 'tutor'];

        foreach (['profesor', 'padre', 'madre', 'tutor', 'estudiante'] as $role) {
            // El filtro de roles de la aplicación devuelve al inicio con un aviso.
            $this->actingAs(User::factory()->create(['role' => $role, 'status' => 'active']))
                ->post("/students/{$student->id}/guardian", $payload)
                ->assertRedirect('/')->assertSessionHas('error');
            $this->assertNull($student->fresh()->parent_user_id, "el rol {$role} pudo asignar un responsable");
        }

        $this->actingAs(User::factory()->create(['role' => 'academic', 'status' => 'active']))
            ->post("/students/{$student->id}/guardian", $payload)->assertRedirect();
        $this->assertSame($tutor->id, (int) $student->fresh()->parent_user_id);
    }
}
