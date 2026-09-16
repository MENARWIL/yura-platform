<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyAndStudentAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_professor_can_view_student_index_with_normalized_columns(): void
    {
        $teacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);

        $parent = User::factory()->create([
            'rol' => 'padre',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        Student::create([
            'name' => 'Ana Paz',
            'course' => '1° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $parent->id,
            'teacher_user_id' => $teacher->id,
            'edad' => 12,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'registration_date' => '2026-05-25',
            'puntaje' => 80,
        ]);

        $this->actingAs($teacher)
            ->get('/students')
            ->assertOk()
            ->assertSee('Ana Paz');
    }

    public function test_admin_can_view_family_members_index(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        $student = Student::create([
            'name' => 'Ana Paz',
            'course' => '1° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'edad' => 12,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'fecha_registro' => '2026-05-25',
            'puntaje' => 80,
        ]);

        $this->actingAs($admin)
            ->get('/family-members')
            ->assertOk();

        $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Ana Paz']);
    }

    public function test_admin_can_create_student_and_assign_a_family_member(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $familyUser = User::factory()->create([
            'rol' => 'madre',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        $response = $this->actingAs($admin)->post('/students', [
            'nombre' => 'Maria del Sol',
            'edad' => 10,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'fecha_registro' => '2026-05-25',
            'parallel_id' => $parallel->id,
            'puntaje' => 88,
            'usuario_id' => $familyUser->id,
            'profesor_id' => null,
            'nota_escritura' => 90,
            'nota_examen' => 91,
            'asistencia' => 97,
        ]);

        $response->assertRedirect('/students');

        $student = Student::where('name', 'Maria del Sol')->firstOrFail();

        $this->assertNull($student->user_id);
        $this->assertDatabaseHas('student_family_members', [
            'student_id' => $student->id,
            'user_id' => $familyUser->id,
            'relation_type' => 'madre',
            'active' => 1,
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $student->user_id,
            'rol' => 'estudiante',
        ]);
    }

    public function test_professor_dashboard_shows_only_his_assigned_students(): void
    {
        $teacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);

        $otherTeacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        Student::create([
            'name' => 'Alumno de profesor',
            'course' => '1° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'teacher_user_id' => $teacher->id,
            'edad' => 12,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'registration_date' => '2026-05-25',
            'puntaje' => 82,
        ]);

        Student::create([
            'name' => 'Alumno de otro profesor',
            'course' => '1° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'teacher_user_id' => $otherTeacher->id,
            'edad' => 13,
            'genero' => 'Masculino',
            'nivel' => 'avanzado',
            'registration_date' => '2026-05-26',
            'puntaje' => 91,
        ]);

        $this->actingAs($teacher)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Alumno de profesor')
            ->assertDontSee('Alumno de otro profesor');
    }

    public function test_professor_student_list_is_scoped_to_his_students_and_hides_register_button(): void
    {
        $teacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);

        $otherTeacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '2° Básico']);
        $parallel = Parallel::create(['name' => '2A', 'course_id' => $course->id, 'max_students' => 25]);

        Student::create([
            'name' => 'Estudiante del profesor',
            'course' => '2° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'teacher_user_id' => $teacher->id,
            'edad' => 11,
            'genero' => 'Masculino',
            'nivel' => 'básico',
            'registration_date' => '2026-05-27',
            'puntaje' => 78,
        ]);

        Student::create([
            'name' => 'Estudiante de otro profesor',
            'course' => '2° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'teacher_user_id' => $otherTeacher->id,
            'edad' => 12,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'registration_date' => '2026-05-28',
            'puntaje' => 86,
        ]);

        $this->actingAs($teacher)
            ->get('/students')
            ->assertOk()
            ->assertSee('Estudiante del profesor')
            ->assertDontSee('Estudiante de otro profesor')
            ->assertDontSeeText('Registrar Estudiante');
    }

    public function test_tutor_dashboard_shows_only_assigned_children_and_blocks_student_list(): void
    {
        $tutor = User::factory()->create([
            'rol' => 'tutor',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        Student::create([
            'name' => 'Hijo del tutor',
            'course' => '1° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $tutor->id,
            'edad' => 10,
            'genero' => 'Masculino',
            'nivel' => 'básico',
            'registration_date' => '2026-05-25',
            'puntaje' => 80,
        ]);

        $this->actingAs($tutor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Hijo del tutor')
            ->assertSee('Mis hijos');

        $this->actingAs($tutor)
            ->get('/students')
            ->assertRedirect('/');
    }

    public function test_tutor_with_more_than_three_children_gets_a_child_selector(): void
    {
        $tutor = User::factory()->create([
            'rol' => 'tutor',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        foreach (range(1, 4) as $number) {
            Student::create([
                'name' => "Hijo {$number}",
                'course' => '1° Básico',
                'course_id' => $course->id,
                'parallel_id' => $parallel->id,
                'parent_user_id' => $tutor->id,
                'edad' => 10,
                'genero' => 'Masculino',
                'nivel' => 'básico',
                'registration_date' => '2026-05-25',
                'puntaje' => 80,
            ]);
        }

        $this->actingAs($tutor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="child-selector"', false)
            ->assertSee('Hijo 1')
            ->assertSee('Hijo 4');
    }

    public function test_student_can_be_registered_with_primary_and_secondary_tutors(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);
        $primaryTutor = User::factory()->create([
            'rol' => 'tutor',
            'estado' => 'activo',
        ]);
        $secondaryTutor = User::factory()->create([
            'rol' => 'tutor',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        $this->actingAs($admin)
            ->post('/students', [
                'name' => 'Estudiante con dos tutores',
                'age' => 10,
                'gender' => 'Masculino',
                'level' => 'básico',
                'registration_date' => '2026-05-25',
                'parallel_id' => $parallel->id,
                'parent_user_id' => $primaryTutor->id,
                'secondary_parent_user_id' => $secondaryTutor->id,
            ])
            ->assertRedirect(route('students.index'));

        $student = Student::where('name', 'Estudiante con dos tutores')->firstOrFail();

        $this->assertDatabaseHas('student_family_members', [
            'student_id' => $student->id,
            'user_id' => $primaryTutor->id,
            'is_primary' => true,
            'active' => true,
        ]);
        $this->assertDatabaseHas('student_family_members', [
            'student_id' => $student->id,
            'user_id' => $secondaryTutor->id,
            'is_primary' => false,
            'active' => true,
        ]);
    }

    public function test_student_account_is_not_created_automatically(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $parent = User::factory()->create([
            'rol' => 'padre',
            'estado' => 'activo',
        ]);

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        $this->actingAs($admin)->post('/students', [
            'nombre' => 'Luciano Quispe',
            'edad' => 11,
            'genero' => 'Masculino',
            'nivel' => 'básico',
            'fecha_registro' => '2026-05-25',
            'parallel_id' => $parallel->id,
            'puntaje' => 70,
            'usuario_id' => $parent->id,
            'profesor_id' => null,
            'nota_escritura' => 80,
            'nota_examen' => 78,
            'asistencia' => 95,
        ]);

        $student = Student::where('name', 'Luciano Quispe')->firstOrFail();

        $this->assertNull($student->user_id);
        $this->assertDatabaseMissing('users', [
            'role' => 'estudiante',
        ]);
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

        $course = Course::create(['name' => '1° Básico']);
        $parallel = Parallel::create(['name' => '1A', 'course_id' => $course->id, 'max_students' => 25]);

        $this->actingAs($admin)->post('/students', [
            'nombre' => 'Sofia Ramos',
            'edad' => 9,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'fecha_registro' => '2026-05-25',
            'parallel_id' => $parallel->id,
            'puntaje' => 82,
            'usuario_id' => $familyUser->id,
            'profesor_id' => null,
            'nota_escritura' => 85,
            'nota_examen' => 88,
            'asistencia' => 96,
        ]);

        $student = Student::where('name', 'Sofia Ramos')->firstOrFail();

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
