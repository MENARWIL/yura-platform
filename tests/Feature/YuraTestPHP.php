<?php

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Datos alineados al seeder de la escuela piloto YURA (Cochabamba).
 */
const YURA_ADMIN_EMAIL = 'admin@yura.com';
const YURA_ADMIN_PASSWORD = 'password123';
const YURA_TEACHER_EMAIL = 'prof.vargas@school.bo';
const YURA_TUTOR_EMAIL = 'juan.quispe@school.bo';
const YURA_TUTOR_CI = '7894561';
const YURA_TUTOR_PHONE = '70111222';
const YURA_STUDENT_NAME = 'María Quispe';

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Administrador Principal',
        'email' => YURA_ADMIN_EMAIL,
        'password' => YURA_ADMIN_PASSWORD,
        'role' => 'admin',
        'status' => 'active',
        'phone' => '70101010',
        'ci' => '10000001',
    ]);

    $this->teacher = User::factory()->create([
        'name' => 'Prof. Carlos Vargas',
        'email' => YURA_TEACHER_EMAIL,
        'password' => YURA_ADMIN_PASSWORD,
        'role' => 'profesor',
        'status' => 'active',
        'phone' => '70987654',
        'ci' => '10000002',
    ]);

    $this->course = Course::create(['name' => 'Sexto de Primaria']);

    $this->parallel = Parallel::create([
        'name' => 'A',
        'course_id' => $this->course->id,
        'max_students' => 20,
    ]);
});

it('inicia sesión con credenciales válidas y redirige al dashboard', function () {
    $response = $this->post('/login', [
        'email' => YURA_ADMIN_EMAIL,
        'password' => YURA_ADMIN_PASSWORD,
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($this->admin);
});

it('registra un tutor activo en users con rol tutor', function () {
    $response = $this->actingAs($this->admin)->post('/tutors', [
        'ci' => YURA_TUTOR_CI,
        'name' => 'Juan',
        'lastname' => 'Quispe',
        'phone' => YURA_TUTOR_PHONE,
        'email' => YURA_TUTOR_EMAIL,
        'password' => YURA_ADMIN_PASSWORD,
    ]);

    $response->assertRedirect(route('students.create'));

    $this->assertDatabaseHas('users', [
        'ci' => YURA_TUTOR_CI,
        'name' => 'Juan Quispe',
        'phone' => YURA_TUTOR_PHONE,
        'email' => YURA_TUTOR_EMAIL,
        'role' => 'tutor',
        'status' => 'active',
    ]);
});

it('registra un alumno y lo asigna al tutor y al profesor', function () {
    $tutor = registerYuraTutor($this->admin);

    $response = $this->actingAs($this->admin)->post('/students', [
        'name' => YURA_STUDENT_NAME,
        'age' => 11,
        'gender' => 'Femenino',
        'level' => 'básico',
        'registration_date' => '2026-03-01',
        'parallel_id' => $this->parallel->id,
        'parent_user_id' => $tutor->id,
        'teacher_user_id' => $this->teacher->id,
        'attendance' => 100,
    ]);

    $response->assertRedirect(route('students.index'));

    $this->assertDatabaseHas('students', [
        'name' => YURA_STUDENT_NAME,
        'parallel_id' => $this->parallel->id,
        'parent_user_id' => $tutor->id,
        'teacher_user_id' => $this->teacher->id,
        'course_id' => $this->course->id,
        'course' => 'Sexto de Primaria',
    ]);
});

it('permite al profesor registrar una nota de actividad en el cuaderno pedagógico', function () {
    $student = registerYuraStudent($this->admin, $this->teacher, $this->parallel);

    $response = $this->actingAs($this->teacher)->post('/grades/save', [
        'student_id' => $student->id,
        'subject_id' => $this->course->id,
        'score' => 87.5,
        'type' => 'activity',
        'activity_number' => 1,
        'quarter' => '1',
        'status' => 'completed',
    ]);

    $response->assertOk()->assertJson([
        'success' => true,
    ]);

    $this->assertDatabaseHas('grades', [
        'student_id' => $student->id,
        'subject_id' => $this->course->id,
        'score' => 87.5,
        'type' => 'activity',
        'quarter' => 1,
        'status' => 'completed',
    ]);
});

it('vincula al familiar del alumno como tutor en student_family_members', function () {
    $student = registerYuraStudent($this->admin, $this->teacher, $this->parallel);
    $tutor = User::where('email', YURA_TUTOR_EMAIL)->firstOrFail();

    $this->assertDatabaseHas('student_family_members', [
        'student_id' => $student->id,
        'user_id' => $tutor->id,
        'relation_type' => 'tutor',
        'is_primary' => 1,
        'can_view' => 1,
        'can_receive_reports' => 1,
        'active' => 1,
    ]);
});

function registerYuraTutor(User $admin): User
{
    test()->actingAs($admin)->post('/tutors', [
        'ci' => YURA_TUTOR_CI,
        'name' => 'Juan',
        'lastname' => 'Quispe',
        'phone' => YURA_TUTOR_PHONE,
        'email' => YURA_TUTOR_EMAIL,
        'password' => YURA_ADMIN_PASSWORD,
    ])->assertRedirect(route('students.create'));

    return User::where('email', YURA_TUTOR_EMAIL)->firstOrFail();
}

function registerYuraStudent(User $admin, User $teacher, Parallel $parallel): Student
{
    $tutor = registerYuraTutor($admin);

    test()->actingAs($admin)->post('/students', [
        'name' => YURA_STUDENT_NAME,
        'age' => 11,
        'gender' => 'Femenino',
        'level' => 'básico',
        'registration_date' => '2026-03-01',
        'parallel_id' => $parallel->id,
        'parent_user_id' => $tutor->id,
        'teacher_user_id' => $teacher->id,
        'attendance' => 100,
    ])->assertRedirect(route('students.index'));

    return Student::where('name', YURA_STUDENT_NAME)->firstOrFail();
}
