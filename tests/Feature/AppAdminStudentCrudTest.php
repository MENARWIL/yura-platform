<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CRUD de estudiantes por la API: solo el administrador crea, actualiza y borra. */
class AppAdminStudentCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Parallel $parallel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $this->parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 30]);
    }

    private function payload(array $over = []): array
    {
        return $over + [
            'last_name' => 'Quispe', 'second_last_name' => 'Mamani', 'first_name' => 'Ana María',
            'age' => 11, 'gender' => 'Femenino', 'level' => 'básico', 'parallel_id' => $this->parallel->id,
        ];
    }

    public function test_admin_creates_shows_updates_lists_and_deletes_a_student(): void
    {
        $api = $this->actingAs($this->admin, 'sanctum');

        // Crear: 201 y el nombre completo se arma solo.
        $id = $api->postJson('/api/v1/app/admin/students', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Quispe Mamani Ana María')
            ->assertJsonPath('data.course', 'Sexto de Primaria')
            ->json('data.id');

        // Mostrar.
        $api->getJson("/api/v1/app/admin/students/{$id}")->assertOk()->assertJsonPath('data.first_name', 'Ana María');

        // Actualizar solo lo que cambia.
        $api->putJson("/api/v1/app/admin/students/{$id}", ['age' => 12, 'level' => 'intermedio'])
            ->assertOk()->assertJsonPath('data.age', 12)->assertJsonPath('data.level', 'intermedio')
            ->assertJsonPath('data.last_name', 'Quispe');

        // Listar con búsqueda y paginación.
        $api->getJson('/api/v1/app/admin/students?q=quispe&per_page=5')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $id);

        // Borrar (lógico) y ya no se encuentra: 404.
        $api->deleteJson("/api/v1/app/admin/students/{$id}")->assertOk();
        $api->getJson("/api/v1/app/admin/students/{$id}")->assertNotFound();
        $this->assertSoftDeleted('students', ['id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.delete', 'entity_id' => $id]);
    }

    public function test_validation_errors_return_422_with_the_fields(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/app/admin/students', $this->payload(['first_name' => '', 'gender' => 'Otro', 'parallel_id' => 999]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'gender', 'parallel_id']);
    }

    public function test_teacher_and_family_cannot_write_and_unknown_ids_are_404(): void
    {
        $student = Student::create($this->payload() + ['course' => 'Sexto de Primaria', 'course_id' => $this->parallel->course_id, 'status' => 'activo']);
        foreach (['profesor', 'padre'] as $role) {
            $user = User::factory()->create(['rol' => $role, 'estado' => 'activo']);
            $api = $this->actingAs($user, 'sanctum');
            $api->postJson('/api/v1/app/admin/students', $this->payload())->assertForbidden();
            $api->putJson("/api/v1/app/admin/students/{$student->id}", ['age' => 13])->assertForbidden();
            $api->deleteJson("/api/v1/app/admin/students/{$student->id}")->assertForbidden();
        }
        $this->assertSame(11, $student->fresh()->age);
        $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/app/admin/students/999999')->assertNotFound();
    }
}
