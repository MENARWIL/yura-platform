<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrudAndMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_user_and_student_photos_and_restore_student(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $parent = User::factory()->create([
            'rol' => 'padre',
            'estado' => 'activo',
        ]);

        $teacher = User::factory()->create([
            'rol' => 'profesor',
            'estado' => 'activo',
        ]);

        $userResponse = $this->actingAs($admin)->post('/users', [
            'name' => 'Maestro de Prueba',
            'email' => 'maestro@example.com',
            'password' => 'Clave#12345',
            'rol' => 'profesor',
            'telefono' => '70010011',
            'foto' => UploadedFile::fake()->image('teacher.jpg'),
        ]);

        $userResponse->assertRedirect('/users');
        $teacherUser = User::where('email', 'maestro@example.com')->firstOrFail();
        $this->assertNotNull($teacherUser->foto_path);
        Storage::disk('public')->assertExists($teacherUser->foto_path);

        $studentResponse = $this->actingAs($admin)->post('/students', [
            'nombre' => 'Estudiante Prueba',
            'edad' => 9,
            'genero' => 'Masculino',
            'nivel' => 'básico',
            'fecha_registro' => '2026-05-23',
            'puntaje' => 80,
            'usuario_id' => $parent->id,
            'profesor_id' => $teacher->id,
            'nota_escritura' => 85,
            'nota_examen' => 90,
            'asistencia' => 100,
            'foto' => UploadedFile::fake()->image('student.jpg'),
        ]);

        $studentResponse->assertRedirect('/students');
        $student = Student::where('nombre', 'Estudiante Prueba')->firstOrFail();
        $this->assertNotNull($student->foto_path);
        Storage::disk('public')->assertExists($student->foto_path);

        $this->actingAs($admin)->delete('/students/' . $student->id)
            ->assertRedirect('/students');

        $this->assertSoftDeleted('estudiantes', [
            'id' => $student->id,
        ]);

        $this->actingAs($admin)->patch('/students/' . $student->id . '/restore')
            ->assertRedirect('/students');

        $this->assertDatabaseHas('estudiantes', [
            'id' => $student->id,
            'deleted_at' => null,
        ]);
    }

    public function test_admin_can_force_delete_user(): void
    {
        $admin = User::factory()->create([
            'rol' => 'admin',
            'estado' => 'activo',
        ]);

        $user = User::factory()->create([
            'rol' => 'padre',
            'estado' => 'activo',
        ]);

        $this->actingAs($admin)
            ->delete('/users/' . $user->id . '/force')
            ->assertRedirect('/users');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
