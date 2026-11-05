<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Observaciones de la revisión «Mejoras YURA WEB.xlsx»: paginación con selector, promoción de cursos,
 * validación del estudiante, confirmaciones propias, avisos, cursos filtrados, auditoría e idioma.
 */
class QaObservationsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    private function parallel(string $course, string $name = 'A', int $max = 25): Parallel
    {
        $c = Course::firstOrCreate(['name' => $course]);

        return Parallel::firstOrCreate(['name' => $name, 'course_id' => $c->id], ['max_students' => $max]);
    }

    private function student(Parallel $parallel, string $name, int $age = 9): Student
    {
        return Student::create([
            'name' => $name, 'course' => $parallel->course->name, 'course_id' => $parallel->course_id, 'parallel_id' => $parallel->id,
            'age' => $age, 'gender' => 'Femenino', 'level' => 'intermedio', 'registration_date' => '2026-02-01', 'puntaje' => 0,
        ]);
    }

    // ------------------------------------------------------------ paginación (fila 10 y hoja 2)

    public function test_admin_lists_have_the_5_10_15_20_page_size_selector(): void
    {
        $admin = $this->user('admin');
        foreach (range(1, 12) as $i) {
            Subject::create(['name' => "Materia {$i}", 'code' => "M{$i}", 'active' => true, 'sort_order' => $i]);
        }

        $this->actingAs($admin)->get('/subjects?per_page=5')->assertOk()
            ->assertSee('Mostrando 1–5 de')
            ->assertSee('<option value="20"', false)
            ->assertSee('page=2', false);
        $this->actingAs($admin)->get('/subjects?per_page=999')->assertOk()->assertSee('Mostrando 1–10 de');

        foreach (['/teachers', '/teaching-assignments', '/admin/years', '/admin/audit'] as $url) {
            $this->actingAs($admin)->get($url . '?per_page=5')->assertOk();
        }
    }

    public function test_the_notebook_is_paginated_and_keeps_the_list_number(): void
    {
        $teacher = $this->user('profesor');
        $parallel = $this->parallel('3° Primaria');
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua', 'active' => true]);
        \App\Models\TeachingAssignment::create(['teacher_user_id' => $teacher->id, 'subject_id' => $subject->id, 'parallel_id' => $parallel->id, 'active' => true]);
        foreach (range(1, 7) as $i) {
            $this->student($parallel, "Alumno Prueba{$i}");
        }

        $html = $this->actingAs($teacher)
            ->get("/grades?subject_id={$subject->id}&parallel_id={$parallel->id}&quarter=1&per_page=5&page=2")
            ->assertOk()->assertSee('Mostrando 6–7 de 7')->getContent();
        $this->assertStringContainsString('<td class="text-center align-middle">6</td>', $html);
    }

    // ------------------------------------------------------------ promoción de curso (fila 13 y hoja 2)

    public function test_director_promotes_a_whole_course_and_repeaters_stay(): void
    {
        $director = $this->user('director');
        $from = $this->parallel('3° Primaria');
        $to = $this->parallel('4° Primaria');
        $ana = $this->student($from, 'Ana Quispe', 9);
        $luis = $this->student($from, 'Luis Mamani', 10);
        $repite = $this->student($from, 'Rosa Choque', 9);

        $this->actingAs($director)->get(route('students.promote', ['from' => $from->id]))->assertOk()
            ->assertSee('Ana Quispe')->assertSee('Rosa Choque');

        $this->actingAs($director)->post(route('students.promote.store'), [
            'from_parallel_id' => $from->id, 'to_parallel_id' => $to->id,
            'student_ids' => [$ana->id, $luis->id], 'add_year' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame($to->id, $ana->fresh()->parallel_id);
        $this->assertSame($to->course_id, $luis->fresh()->course_id);
        $this->assertSame(10, (int) $ana->fresh()->age);
        $this->assertSame(11, (int) $luis->fresh()->age);
        $this->assertSame($from->id, $repite->fresh()->parallel_id, 'quien repite se queda');
        $this->assertSame($to->id, Enrollment::where('student_id', $ana->id)->where('academic_year_id', AcademicYear::currentId())->value('parallel_id'));
        $this->assertSame(1, AuditLog::where('action', 'students.promote')->count());
    }

    public function test_promotion_is_refused_when_the_course_already_has_grades_this_year(): void
    {
        $from = $this->parallel('3° Primaria');
        $to = $this->parallel('4° Primaria');
        $ana = $this->student($from, 'Ana Quispe');
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        Grade::create(['student_id' => $ana->id, 'subject_id' => $subject->id, 'score' => 80, 'type' => 'activity', 'status' => 'completed', 'quarter' => 1, 'activity_number' => 1]);

        $this->actingAs($this->user('admin'))->post(route('students.promote.store'), [
            'from_parallel_id' => $from->id, 'to_parallel_id' => $to->id, 'student_ids' => [$ana->id],
        ])->assertSessionHas('error');

        $this->assertSame($from->id, $ana->fresh()->parallel_id);
    }

    public function test_promotion_respects_the_destination_capacity(): void
    {
        $from = $this->parallel('3° Primaria');
        $to = $this->parallel('4° Primaria', 'A', 1);
        $a = $this->student($from, 'Ana Quispe');
        $b = $this->student($from, 'Luis Mamani');

        $this->actingAs($this->user('admin'))->post(route('students.promote.store'), [
            'from_parallel_id' => $from->id, 'to_parallel_id' => $to->id, 'student_ids' => [$a->id, $b->id],
        ])->assertSessionHas('error');

        $this->assertSame($from->id, $a->fresh()->parallel_id);
    }

    public function test_the_administrative_panel_cannot_promote(): void
    {
        $from = $this->parallel('3° Primaria');
        $to = $this->parallel('4° Primaria');
        $ana = $this->student($from, 'Ana Quispe');
        $secretary = $this->user('academic');

        $this->actingAs($secretary)->get(route('students.promote'))->assertForbidden();
        $this->actingAs($secretary)->post(route('students.promote.store'), [
            'from_parallel_id' => $from->id, 'to_parallel_id' => $to->id, 'student_ids' => [$ana->id],
        ])->assertForbidden();
        $this->actingAs($secretary)->get('/students')->assertDontSee(route('students.promote'), false);
    }

    // ------------------------------------------------------------ formulario del estudiante (filas 14 y 15)

    public function test_student_form_uses_yura_live_validation_instead_of_browser_bubbles(): void
    {
        $this->parallel('3° Primaria');
        $html = $this->actingAs($this->user('admin'))->get('/students/create')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<form[^>]*students[^>]*novalidate/', $html);
        $this->assertMatchesRegularExpression('/name="first_name"[^>]*data-rule="name"/', $html);
        $this->assertStringContainsString('js/user-form-validation.js', $html);
    }

    // ------------------------------------------------------------ confirmaciones y avisos (filas 16 y 17)

    public function test_deletes_use_the_web_confirmation_and_success_alerts_hide_themselves(): void
    {
        $admin = $this->user('admin');
        $this->user('padre');

        $html = $this->actingAs($admin)->get('/users')->assertOk()->getContent();
        $this->assertStringContainsString('data-confirm="¿Estás seguro de eliminar este usuario?"', $html);
        $this->assertStringNotContainsString('return confirm(', $html);

        $this->actingAs($admin)->withSession(['success' => 'Actualizado con éxito'])->get('/dashboard')
            ->assertSee('data-autohide', false);
        foreach (glob(resource_path('views/**/*.blade.php')) as $view) {
            $this->assertStringNotContainsString('return confirm(', file_get_contents($view), $view);
        }
    }

    // ------------------------------------------------------------ cursos y paralelos (fila 18)

    public function test_courses_can_be_filtered_and_are_collapsed(): void
    {
        $this->parallel('3° Primaria');
        $this->parallel('4° Primaria');
        $admin = $this->user('admin');

        $this->actingAs($admin)->get('/courses')->assertOk()->assertSee('Abrir todos')->assertSee('collapsed', false);
        $only = Course::where('name', '4° Primaria')->value('id');
        $this->actingAs($admin)->get("/courses?course_id={$only}")->assertOk()
            ->assertSee('4° Primaria')->assertSee('id="course-body-' . $only . '"', false)
            ->assertDontSee('id="course-body-' . Course::where('name', '3° Primaria')->value('id') . '"', false);
    }

    // ------------------------------------------------------------ notas, auditoría e idioma (filas 3, 20 y 21)

    public function test_single_grade_edit_only_accepts_whole_numbers_from_1_to_100(): void
    {
        $teacher = $this->user('profesor');
        $parallel = $this->parallel('3° Primaria');
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua', 'active' => true]);
        \App\Models\TeachingAssignment::create(['teacher_user_id' => $teacher->id, 'subject_id' => $subject->id, 'parallel_id' => $parallel->id, 'active' => true]);
        $student = $this->student($parallel, 'Ana Quispe');
        $grade = Grade::create(['student_id' => $student->id, 'subject_id' => $subject->id, 'score' => 70, 'type' => 'activity', 'status' => 'completed', 'quarter' => 1, 'activity_number' => 1]);

        foreach ([0, 345, -5, 50.5] as $bad) {
            $this->actingAs($teacher)->put(route('grades.update', $grade), ['score' => $bad])->assertSessionHasErrors('score');
        }
        $this->assertEquals(70, $grade->fresh()->score);
    }

    public function test_audit_hides_technical_fields(): void
    {
        $student = $this->student($this->parallel('3° Primaria'), 'Victor Aduviri');
        AuditLog::create(['action' => 'student_info_updated', 'entity' => 'student', 'entity_id' => $student->id, 'status' => 'success',
            'details' => ['fields' => ['age', 'gender', 'level', 'updated_at']]]);

        $this->actingAs($this->user('admin'))->get('/admin/audit')->assertOk()
            ->assertSee('Se completó la edad, el género y el nivel de')
            ->assertDontSee('updated_at');
    }

    public function test_the_panel_stays_in_spanish_and_has_no_language_selector(): void
    {
        $admin = $this->user('admin');

        $html = $this->actingAs($admin)->withSession(['locale' => 'qu'])->get('/dashboard')->assertOk()->getContent();
        $this->assertStringNotContainsString(route('lang.set', ['locale' => 'qu']), $html);
        $this->assertStringContainsString('<html lang="es"', $html);
    }
}
