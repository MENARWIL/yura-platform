<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pruebas de regresión para los 6 hallazgos críticos de la auditoría de
 * seguridad y autorización (2026-09-17). Cada test reproduce el escenario
 * exacto que antes del fix permitía una escalada de privilegios o una fuga
 * de datos entre familias, y que ahora debe estar bloqueado.
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Hallazgo 1: un usuario no-admin (rol padre) no debe poder listar
     * todos los usuarios vía GET /api/users. Antes del fix, UserController@index
     * no validaba isAdmin() y devolvía 200 con todos los usuarios.
     */
    public function test_non_admin_cannot_list_users_via_api(): void
    {
        $parent = User::factory()->create([
            'role' => 'padre',
            'status' => 'active',
        ]);
        User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($parent, 'sanctum')
            ->getJson('/api/users')
            ->assertStatus(403);
    }

    /**
     * Contraste positivo del hallazgo 1: un admin sí puede listar usuarios.
     */
    public function test_admin_can_list_users_via_api(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /**
     * Hallazgo 2: un usuario no-admin (rol padre) no debe poder crear un
     * usuario (mucho menos uno admin) vía POST /api/users. Antes del fix,
     * UserController@store no validaba isAdmin() y creaba el registro.
     */
    public function test_non_admin_cannot_create_admin_user_via_api(): void
    {
        $parent = User::factory()->create([
            'role' => 'padre',
            'status' => 'active',
        ]);

        $this->actingAs($parent, 'sanctum')
            ->postJson('/api/users', [
                'name' => 'Usuario Intruso',
                'email' => 'intruso@example.test',
                'password' => 'password123',
                'rol' => 'admin',
                'telefono' => '70000000',
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('users', [
            'email' => 'intruso@example.test',
        ]);
    }

    /**
     * Hallazgo 3: la exportación masiva de estudiantes (PDF/Excel) debe
     * estar bloqueada para el rol padre y permitida para admin/profesor.
     * Antes del fix las rutas de exportación masiva no tenían middleware
     * de rol.
     */
    public function test_parent_is_blocked_from_bulk_student_exports_but_admin_and_teacher_are_allowed(): void
    {
        $parent = User::factory()->create([
            'role' => 'padre',
            'status' => 'active',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $teacher = User::factory()->create([
            'role' => 'profesor',
            'status' => 'active',
        ]);

        $parentPdfResponse = $this->actingAs($parent)->get('/students/export-pdf-all');
        $this->assertNotSame(200, $parentPdfResponse->getStatusCode());

        $parentExcelResponse = $this->actingAs($parent)->get('/students/export-excel-all');
        $this->assertNotSame(200, $parentExcelResponse->getStatusCode());

        $this->actingAs($admin)
            ->get('/students/export-pdf-all')
            ->assertOk();

        $this->actingAs($teacher)
            ->get('/students/export-excel-all')
            ->assertOk();
    }

    /**
     * Hallazgo 4: un padre solo debe ver a su(s) propio(s) hijo(s) en
     * GET /students, nunca a los estudiantes de otras familias. Antes del
     * fix, StudentController@index (web) no escalaba por rol como sí lo
     * hacía la API.
     */
    public function test_parent_only_sees_own_children_in_students_index(): void
    {
        $parentA = User::factory()->create([
            'role' => 'padre',
            'status' => 'active',
        ]);
        $parentB = User::factory()->create([
            'role' => 'madre',
            'status' => 'active',
        ]);

        $course = Course::create(['name' => '3° Básico']);
        $parallel = Parallel::create(['name' => '3A', 'course_id' => $course->id, 'max_students' => 25]);

        Student::create([
            'name' => 'Hijo de Familia A',
            'course' => '3° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $parentA->id,
            'age' => 9,
            'gender' => 'Masculino',
            'level' => 'básico',
            'registration_date' => '2026-05-25',
            'score' => 80,
        ]);

        Student::create([
            'name' => 'Hijo de Familia B',
            'course' => '3° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $parentB->id,
            'age' => 8,
            'gender' => 'Femenino',
            'level' => 'básico',
            'registration_date' => '2026-05-25',
            'score' => 85,
        ]);

        $this->actingAs($parentA)
            ->get('/students')
            ->assertOk()
            ->assertSee('Hijo de Familia A')
            ->assertDontSee('Hijo de Familia B');
    }

    /**
     * Hallazgo 5: un usuario con status inactivo no puede iniciar sesión
     * desde el formulario web, aunque las credenciales sean correctas.
     * Antes del fix, AuthController@login no verificaba el status tras
     * Auth::attempt().
     */
    public function test_inactive_user_cannot_login_via_web_form(): void
    {
        $user = User::factory()->create([
            'email' => 'inactivo@example.test',
            'password' => Hash::make('password123'),
            'role' => 'padre',
            'status' => 'inactivo',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $response->assertRedirect();
        $this->assertNotSame('/dashboard', $response->headers->get('Location'));
    }

    /**
     * Hallazgo 6: un usuario rol padre/madre puede acceder a su dashboard
     * familiar y a GET /api/tutor/my-children con sus hijos reales (200,
     * no 403); y no ve familiares/registros de otra familia en
     * /family-members. Antes del fix, DashboardController, TutorController
     * y FamilyMemberController usaban isTutor()/role==='tutor', que
     * excluía a los roles padre/madre.
     */
    public function test_responsible_role_can_access_family_dashboard_and_my_children_api_scoped_to_own_family(): void
    {
        $parentA = User::factory()->create([
            'role' => 'padre',
            'status' => 'active',
        ]);
        $parentB = User::factory()->create([
            'role' => 'madre',
            'status' => 'active',
        ]);

        $course = Course::create(['name' => '4° Básico']);
        $parallel = Parallel::create(['name' => '4A', 'course_id' => $course->id, 'max_students' => 25]);

        $childA = Student::create([
            'name' => 'Hijo Real de A',
            'course' => '4° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $parentA->id,
            'age' => 10,
            'gender' => 'Masculino',
            'level' => 'básico',
            'registration_date' => '2026-05-25',
            'score' => 75,
        ]);

        $childB = Student::create([
            'name' => 'Hijo Real de B',
            'course' => '4° Básico',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $parentB->id,
            'age' => 9,
            'gender' => 'Femenino',
            'level' => 'básico',
            'registration_date' => '2026-05-25',
            'score' => 79,
        ]);

        StudentFamilyMember::create([
            'student_id' => $childA->id,
            'user_id' => $parentA->id,
            'relation_type' => 'padre',
            'is_primary' => true,
            'can_view' => true,
            'can_edit' => false,
            'can_receive_reports' => true,
            'active' => true,
        ]);

        StudentFamilyMember::create([
            'student_id' => $childB->id,
            'user_id' => $parentB->id,
            'relation_type' => 'madre',
            'is_primary' => true,
            'can_view' => true,
            'can_edit' => false,
            'can_receive_reports' => true,
            'active' => true,
        ]);

        // Dashboard familiar accesible para rol padre/madre.
        $this->actingAs($parentA)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Hijo Real de A');

        // GET /api/tutor/my-children devuelve 200 con los hijos reales, no 403.
        $response = $this->actingAs($parentA, 'sanctum')
            ->getJson('/api/tutor/my-children')
            ->assertOk()
            ->assertJsonPath('success', true);

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Hijo Real de A'));
        $this->assertFalse($names->contains('Hijo Real de B'));

        // /family-members solo muestra el registro de la propia familia.
        $this->actingAs($parentA)
            ->get('/family-members')
            ->assertOk()
            ->assertSee('Hijo Real de A')
            ->assertDontSee('Hijo Real de B');
    }
}
