<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyAndStudentAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_student_account_and_assign_a_family_member(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $familyUser = User::factory()->create([
            'rol' => 'madre',
            'estado' => 'activo',
        ]);

        $response = $this->actingAs($admin)->post('/students', [
            'nombre' => 'Maria del Sol',
            'edad' => 10,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'fecha_registro' => '2026-05-25',
            'puntaje' => 88,
            'usuario_id' => $familyUser->id,
            'profesor_id' => null,
            'nota_escritura' => 90,
            'nota_examen' => 91,
            'asistencia' => 97,
        ]);

        $response->assertRedirect('/students');

        $student = Student::where('nombre', 'Maria del Sol')->firstOrFail();

        $this->assertNotNull($student->user_id);
        $this->assertDatabaseHas('users', [
            'id' => $student->user_id,
            'rol' => 'estudiante',
        ]);

        $studentAccount = User::findOrFail($student->user_id);
        $this->assertTrue($studentAccount->isEstudiante());

        $familyResponse = $this->actingAs($admin)->post('/family-members', [
            'student_id' => $student->id,
            'user_id' => $familyUser->id,
            'relation_type' => 'madre',
            'is_primary' => false,
            'can_view' => true,
            'can_edit' => false,
            'can_receive_reports' => true,
            'active' => true,
        ]);

        $familyResponse->assertRedirect('/family-members');

        $this->assertDatabaseHas('student_family_members', [
            'student_id' => $student->id,
            'user_id' => $familyUser->id,
            'relation_type' => 'madre',
            'active' => 1,
        ]);
    }

    public function test_student_can_login_with_its_automatic_account(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $parent = User::factory()->create([
            'rol' => 'padre',
            'estado' => 'activo',
        ]);

        $this->actingAs($admin)->post('/students', [
            'nombre' => 'Luciano Quispe',
            'edad' => 11,
            'genero' => 'Masculino',
            'nivel' => 'básico',
            'fecha_registro' => '2026-05-25',
            'puntaje' => 70,
            'usuario_id' => $parent->id,
            'profesor_id' => null,
            'nota_escritura' => 80,
            'nota_examen' => 78,
            'asistencia' => 95,
        ]);

        $student = Student::where('nombre', 'Luciano Quispe')->firstOrFail();
        $studentAccount = User::findOrFail($student->user_id);

        $loginResponse = $this->post('/login', [
            'email' => $studentAccount->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect('/dashboard');
    }

    public function test_admin_can_update_and_delete_a_family_member_relationship(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $familyUser = User::factory()->create([
            'rol' => 'madre',
            'estado' => 'activo',
        ]);

        $this->actingAs($admin)->post('/students', [
            'nombre' => 'Sofia Ramos',
            'edad' => 9,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'fecha_registro' => '2026-05-25',
            'puntaje' => 82,
            'usuario_id' => $familyUser->id,
            'profesor_id' => null,
            'nota_escritura' => 85,
            'nota_examen' => 88,
            'asistencia' => 96,
        ]);

        $student = Student::where('nombre', 'Sofia Ramos')->firstOrFail();

        $this->actingAs($admin)->post('/family-members', [
            'student_id' => $student->id,
            'user_id' => $familyUser->id,
            'relation_type' => 'madre',
            'is_primary' => false,
            'can_view' => true,
            'can_edit' => false,
            'can_receive_reports' => true,
            'active' => true,
        ])->assertRedirect('/family-members');

        $familyMember = \App\Models\StudentFamilyMember::where('student_id', $student->id)
            ->where('user_id', $familyUser->id)
            ->firstOrFail();

        $this->actingAs($admin)->put('/family-members/' . $familyMember->id, [
            'relation_type' => 'tutor',
            'is_primary' => true,
            'can_view' => true,
            'can_edit' => true,
            'can_receive_reports' => false,
            'active' => false,
        ])->assertRedirect('/family-members');

        $this->assertDatabaseHas('student_family_members', [
            'id' => $familyMember->id,
            'relation_type' => 'tutor',
            'is_primary' => 1,
            'can_view' => 1,
            'can_edit' => 1,
            'can_receive_reports' => 0,
            'active' => 0,
        ]);

        $this->actingAs($admin)->delete('/family-members/' . $familyMember->id)
            ->assertRedirect('/family-members');

        $this->assertDatabaseMissing('student_family_members', [
            'id' => $familyMember->id,
        ]);
    }
}
