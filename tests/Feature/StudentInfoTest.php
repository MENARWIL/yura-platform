<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pestaña "Datos del estudiante" de la ventana "Editar información": completa
 * nivel, edad y género cuando la ficha quedó incompleta.
 */
class StudentInfoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
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

    public function test_the_card_flags_missing_data_and_offers_to_complete_it(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())->get("/grades/{$student->id}")->assertOk()
            ->assertSee('Datos del estudiante')
            ->assertSee('Sin registrar')
            ->assertSee('data-gm-tab="info"', false)
            ->assertDontSee('name="teacher_user_id"', false)
            ->assertDontSee('gm-teacher', false);
    }

    public function test_complete_data_is_shown_without_the_shortcut(): void
    {
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active', 'name' => 'Profesora Lima']);
        $student = $this->student(['level' => 'básico', 'age' => 11, 'gender' => 'Femenino', 'teacher_user_id' => $teacher->id]);

        $this->actingAs($this->admin())->get("/grades/{$student->id}")->assertOk()
            ->assertSee('BÁSICO')
            ->assertSee('11')
            ->assertSee('Profesora Lima')
            ->assertDontSee('data-gm-tab="info"', false);
    }

    public function test_it_completes_the_missing_data(): void
    {
        $student = $this->student();

        $this->actingAs($this->admin())->patch("/students/{$student->id}/info", [
            'level' => 'intermedio', 'age' => '12', 'gender' => 'Masculino',
        ])->assertRedirect(route('grades.show', $student))->assertSessionHas('success');

        $fresh = $student->fresh();
        $this->assertSame('intermedio', $fresh->level);
        $this->assertSame(12, (int) $fresh->age);
        $this->assertSame('Masculino', $fresh->gender);

        $log = AuditLog::where('action', 'student_info_updated')->where('entity_id', $student->id)->first();
        $this->assertNotNull($log);
    }

    public function test_blank_fields_do_not_erase_data(): void
    {
        $student = $this->student(['level' => 'avanzado', 'age' => 10, 'gender' => 'Femenino']);

        $this->actingAs($this->admin())->patch("/students/{$student->id}/info", [
            'level' => '', 'age' => '', 'gender' => '',
        ])->assertRedirect(route('grades.show', $student));

        $fresh = $student->fresh();
        $this->assertSame('avanzado', $fresh->level);
        $this->assertSame(10, (int) $fresh->age);
        $this->assertSame('Femenino', $fresh->gender);
    }

    public function test_it_rejects_invalid_values(): void
    {
        $student = $this->student();
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/students/{$student->id}/info", ['level' => 'experto', 'age' => '200', 'gender' => 'X'])
            ->assertSessionHasErrorsIn('info', ['level', 'age', 'gender']);

        $this->assertNull($student->fresh()->level);
    }

    public function test_the_teacher_is_not_changed_from_this_window(): void
    {
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $student = $this->student();

        $this->actingAs($this->admin())->patch("/students/{$student->id}/info", ['level' => 'básico', 'teacher_user_id' => $teacher->id])
            ->assertRedirect(route('grades.show', $student));

        $fresh = $student->fresh();
        $this->assertSame('básico', $fresh->level);
        $this->assertNull($fresh->teacher_user_id, 'el profesor se asigna en otra pantalla, no aquí');
    }

    public function test_only_admin_and_academic_can_edit_the_data(): void
    {
        $student = $this->student();

        foreach (['profesor', 'padre', 'tutor', 'estudiante'] as $role) {
            $this->actingAs($this->admin($role))->patch("/students/{$student->id}/info", ['level' => 'básico', 'age' => '9'])
                ->assertRedirect('/')->assertSessionHas('error');
        }
        $this->assertNull($student->fresh()->level);

        $this->actingAs($this->admin('academic'))->patch("/students/{$student->id}/info", ['level' => 'básico', 'age' => '9'])
            ->assertRedirect(route('grades.show', $student));
        $this->assertSame('básico', $student->fresh()->level);
    }
}
