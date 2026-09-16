<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AcademicSubjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_subject(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $this->actingAs($admin)
            ->post('/subjects', [
                'name' => 'Matemáticas',
                'code' => 'MAT',
                'sort_order' => 2,
            ])
            ->assertRedirect(route('subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'name' => 'Matemáticas',
            'code' => 'MAT',
            'active' => true,
        ]);
    }

    public function test_teacher_is_created_with_login_account_and_main_subject(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);
        $subject = Subject::where('code', 'QUE')->firstOrFail();

        $this->actingAs($admin)
            ->post('/teachers', [
                'name' => 'Profesora Quechua',
                'email' => 'profesora.quechua@example.test',
                'password' => 'Password!123',
                'telefono' => '70000000',
                'main_subject_id' => $subject->id,
            ])
            ->assertRedirect(route('teachers.index'));

        $teacher = User::where('email', 'profesora.quechua@example.test')->firstOrFail();

        $this->assertSame('profesor', $teacher->role);
        $this->assertSame($subject->id, $teacher->main_subject_id);
        $this->assertTrue(Hash::check('Password!123', $teacher->password));
    }

    public function test_subject_can_be_assigned_once_per_parallel(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);
        $teacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);
        $secondTeacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);
        $subject = Subject::where('code', 'QUE')->firstOrFail();
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 20]);

        $payload = [
            'teacher_user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'parallel_id' => $parallel->id,
        ];

        $this->actingAs($admin)
            ->post('/teaching-assignments', $payload)
            ->assertRedirect(route('teaching-assignments.index'));

        $this->actingAs($admin)
            ->post('/teaching-assignments', $payload + ['teacher_user_id' => $secondTeacher->id])
            ->assertSessionHasErrors('subject_id');

        $this->assertDatabaseCount('teaching_assignments', 1);
    }

    public function test_teacher_can_open_assigned_notebook_and_save_a_grade(): void
    {
        $teacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);
        $otherTeacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);
        $subject = Subject::where('code', 'QUE')->firstOrFail();
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 20]);
        $student = Student::create([
            'name' => 'Alumno del cuaderno',
            'course' => $course->name,
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'teacher_user_id' => $teacher->id,
            'age' => 11,
            'gender' => 'Masculino',
            'level' => 'básico',
            'registration_date' => '2026-05-25',
        ]);

        TeachingAssignment::create([
            'teacher_user_id' => $teacher->id,
            'subject_id' => $subject->id,
            'parallel_id' => $parallel->id,
            'active' => true,
        ]);

        $this->actingAs($teacher)
            ->get('/grades?subject_id=' . $subject->id . '&parallel_id=' . $parallel->id)
            ->assertOk()
            ->assertSee('Alumno del cuaderno');

        $this->actingAs($teacher)
            ->postJson('/grades/save', [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'type' => 'activity',
                'activity_number' => 1,
                'quarter' => 1,
                'score' => 87,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('grades', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score' => 87,
        ]);

        $this->actingAs($otherTeacher)
            ->postJson('/grades/save', [
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'type' => 'activity',
                'activity_number' => 2,
                'quarter' => 1,
                'score' => 90,
            ])
            ->assertForbidden();
    }
}