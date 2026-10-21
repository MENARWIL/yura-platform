<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * Fallas F1, F3, F4, F5 y F6 del reporte Web y Móvil v1.0.
 */
class ApiHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(string $name, ?User $parent = null): Student
    {
        $course = Course::firstOrCreate(['name' => '6° Primaria']);
        $parallel = Parallel::firstOrCreate(
            ['name' => 'A', 'course_id' => $course->id],
            ['max_students' => 25]
        );

        return Student::create([
            'name' => $name,
            'course' => '6° Primaria',
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'parent_user_id' => $parent?->id,
            'edad' => 11,
            'genero' => 'Femenino',
            'nivel' => 'intermedio',
            'registration_date' => '2026-05-25',
            'puntaje' => 80,
        ]);
    }

    private function linkFamily(User $user, Student $student, array $pivot = []): void
    {
        $user->familyStudents()->attach($student->id, array_merge([
            'relation_type' => 'tutor',
            'is_primary' => false,
            'can_view' => true,
            'can_edit' => false,
            'can_receive_reports' => true,
            'active' => true,
        ], $pivot));
    }

    private function family(string $role = 'tutor'): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'active']);
    }

    // ---------------------------------------------------------------- F1

    public function test_inactive_family_link_cannot_see_student_through_api(): void
    {
        $tutor = $this->family();
        $student = $this->makeStudent('Ana Paz');
        $this->linkFamily($tutor, $student, ['active' => false]);

        $names = collect($this->actingAs($tutor, 'sanctum')->getJson('/api/students')->assertOk()->json('data'))
            ->pluck('name');
        $this->assertFalse($names->contains('Ana Paz'), 'index muestra un vínculo inactivo');

        $this->actingAs($tutor, 'sanctum')->getJson("/api/students/{$student->id}")->assertForbidden();

        $dashboard = $this->actingAs($tutor, 'sanctum')->getJson('/api/dashboard')->assertOk();
        $this->assertCount(0, $dashboard->json('mis_hijos'), 'dashboard muestra un vínculo inactivo');
    }

    public function test_family_link_without_view_permission_cannot_see_student_through_api(): void
    {
        $tutor = $this->family();
        $student = $this->makeStudent('Luis Rojas');
        $this->linkFamily($tutor, $student, ['can_view' => false]);

        $this->actingAs($tutor, 'sanctum')->getJson("/api/students/{$student->id}")->assertForbidden();
    }

    public function test_active_family_link_still_sees_student_through_api(): void
    {
        $tutor = $this->family();
        $student = $this->makeStudent('Rosa Vega');
        $this->linkFamily($tutor, $student);

        $this->actingAs($tutor, 'sanctum')->getJson("/api/students/{$student->id}")->assertOk();
        $this->assertCount(1, $this->actingAs($tutor, 'sanctum')->getJson('/api/dashboard')->json('mis_hijos'));
    }

    public function test_main_parent_with_revoked_link_loses_access_but_without_link_keeps_it(): void
    {
        $revoked = $this->family('padre');
        $untouched = $this->family('madre');
        $studentA = $this->makeStudent('Revocado', $revoked);
        $studentB = $this->makeStudent('Sin vinculo', $untouched);
        $this->linkFamily($revoked, $studentA, ['active' => false, 'relation_type' => 'padre', 'is_primary' => true]);

        $this->actingAs($revoked, 'sanctum')->getJson("/api/v1/app/children/{$studentA->id}/summary")->assertForbidden();
        $this->assertSame([], $this->actingAs($revoked, 'sanctum')->getJson('/api/v1/app/children')->json('data'));

        // Sin fila de vínculo, el responsable principal conserva su acceso.
        $this->actingAs($untouched, 'sanctum')->getJson("/api/v1/app/children/{$studentB->id}/summary")->assertOk();
    }

    // ---------------------------------------------------------------- F3

    public function test_login_token_expires_in_30_days_and_expired_token_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'padre@school.bo', 'role' => 'padre', 'status' => 'active',
            'password' => bcrypt('Correcta#2026'),
        ]);

        $token = $this->postJson('/api/login', ['email' => 'padre@school.bo', 'password' => 'Correcta#2026'])
            ->assertOk()->json('access_token');

        $record = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        $this->assertNotNull($record->expires_at, 'el token de la app no vence');
        $this->assertEqualsWithDelta(30, now()->diffInDays($record->expires_at), 1);

        $this->withToken($token)->getJson('/api/user')->assertOk();

        $record->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_robot_token_created_without_expiry_keeps_working(): void
    {
        $robot = User::factory()->create(['role' => 'robot', 'status' => 'active']);
        $token = $robot->createToken('yura-cerebro')->plainTextToken;

        $this->assertNull(\Laravel\Sanctum\PersonalAccessToken::findToken($token)->expires_at);
        $this->withToken($token)->getJson('/api/user')->assertOk();
    }

    public function test_robot_token_issued_by_login_does_not_expire(): void
    {
        User::factory()->create([
            'email' => 'robot@yura.bo', 'role' => 'robot', 'status' => 'active',
            'password' => bcrypt('Correcta#2026'),
        ]);

        $token = $this->postJson('/api/login', ['email' => 'robot@yura.bo', 'password' => 'Correcta#2026'])
            ->assertOk()->json('access_token');

        $this->assertNull(PersonalAccessToken::findToken($token)->expires_at);
    }

    // ---------------------------------------------------------------- F4

    public function test_only_robot_role_can_post_to_legacy_robot_activity_endpoint(): void
    {
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $parent = $this->family('padre');

        foreach ([$teacher, $parent] as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson('/api/v1/robot/activity', [])
                ->assertForbidden();
        }
    }

    // ---------------------------------------------------------------- F5

    public function test_login_is_rate_limited_per_account(): void
    {
        RateLimiter::clear('api-login');
        User::factory()->create(['email' => 'padre@school.bo', 'role' => 'padre', 'status' => 'active']);

        $last = null;
        for ($i = 0; $i < 12; $i++) {
            $last = $this->postJson('/api/login', ['email' => 'padre@school.bo', 'password' => 'incorrecta']);
        }

        $last->assertStatus(429);
    }

    public function test_rate_limit_on_one_account_does_not_block_another(): void
    {
        User::factory()->create(['email' => 'a@school.bo', 'role' => 'padre', 'status' => 'active']);
        User::factory()->create(['email' => 'b@school.bo', 'role' => 'padre', 'status' => 'active', 'password' => bcrypt('Correcta#2026')]);

        for ($i = 0; $i < 12; $i++) {
            $this->postJson('/api/login', ['email' => 'a@school.bo', 'password' => 'mala']);
        }

        $this->postJson('/api/login', ['email' => 'b@school.bo', 'password' => 'Correcta#2026'])->assertOk();
    }

    // ---------------------------------------------------------------- F6

    public function test_suspended_user_token_stops_working(): void
    {
        $tutor = $this->family();
        $token = $tutor->createToken('app')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')->assertOk();

        $tutor->forceFill(['status' => 'inactive'])->save();
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/user')->assertStatus(403);
    }
}
