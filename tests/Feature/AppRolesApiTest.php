<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\RobotActivity;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Fase 2 del plan v1.1: API por roles (docs/api-app-v1.md, «Ampliación v1.1»).
 */
class AppRolesApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'active'], $extra));
    }

    private function student(string $name, ?User $teacher = null, ?User $parent = null): Student
    {
        $course = Course::firstOrCreate(['name' => '6° Primaria']);
        $parallel = Parallel::firstOrCreate(['name' => 'A', 'course_id' => $course->id], ['max_students' => 25]);

        return Student::create([
            'name' => $name, 'course' => '6° Primaria', 'course_id' => $course->id, 'parallel_id' => $parallel->id,
            'parent_user_id' => $parent?->id, 'teacher_user_id' => $teacher?->id,
            'edad' => 11, 'genero' => 'Femenino', 'nivel' => 'intermedio', 'registration_date' => '2026-05-25', 'puntaje' => 80,
        ]);
    }

    private function newUserPayload(array $override = []): array
    {
        return array_merge([
            'first_name' => 'Luis', 'last_name' => 'Rojas', 'second_last_name' => 'Vega',
            'email' => 'luis.rojas@school.bo', 'password' => 'Clave#12345', 'rol' => 'padre', 'telefono' => '70010011',
        ], $override);
    }

    // ------------------------------------------------------------ estudiantes

    public function test_teacher_sees_only_own_students_and_summaries(): void
    {
        $teacher = $this->user('profesor');
        $mine = $this->student('Ana Paz', $teacher);
        $theirs = $this->student('Hijo Ajeno', $this->user('profesor'));

        $names = collect($this->actingAs($teacher, 'sanctum')->getJson('/api/v1/app/students')->assertOk()->json('data'))->pluck('name');
        $this->assertSame(['Ana Paz'], $names->all());

        $this->actingAs($teacher, 'sanctum')->getJson("/api/v1/app/students/{$mine->id}/summary")->assertOk()
            ->assertJsonPath('data.name', 'Ana Paz')->assertJsonStructure(['data' => ['report' => ['average', 'quarter_averages', 'subjects']]]);
        $this->actingAs($teacher, 'sanctum')->getJson("/api/v1/app/students/{$theirs->id}/summary")->assertForbidden();
    }

    public function test_admin_sees_all_students_with_search_and_pagination_meta(): void
    {
        $admin = $this->user('admin');
        $this->student('Ana Paz');
        $this->student('Luis Rojas');

        $all = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/students')->assertOk();
        $this->assertSame(2, $all->json('meta.total'));
        $this->assertSame(1, $all->json('meta.page'));

        $found = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/students?q=Luis')->assertOk();
        $this->assertSame(['Luis Rojas'], collect($found->json('data'))->pluck('name')->all());
    }

    public function test_students_can_be_filtered_by_course_and_parallel(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('profesor');
        $course = Course::firstOrCreate(['name' => '6° Primaria']);
        $other = Course::create(['name' => '5° Primaria']);
        $a = Parallel::firstOrCreate(['name' => 'A', 'course_id' => $course->id], ['max_students' => 40]);
        $b = Parallel::create(['name' => 'B', 'course_id' => $course->id, 'max_students' => 40]);
        $c5 = Parallel::create(['name' => 'A', 'course_id' => $other->id, 'max_students' => 40]);
        foreach ([['Ana Uno', $a, $course, $teacher], ['Beto Dos', $b, $course, $teacher], ['Caro Tres', $c5, $other, null]] as [$name, $p, $c, $t]) {
            Student::create([
                'name' => $name, 'course' => $c->name, 'course_id' => $c->id, 'parallel_id' => $p->id,
                'teacher_user_id' => $t?->id, 'status' => 'activo',
            ]);
        }

        $names = fn ($user, $qs) => collect($this->actingAs($user, 'sanctum')->getJson("/api/v1/app/students?$qs")->assertOk()->json('data'))->pluck('name')->sort()->values()->all();

        $this->assertSame(['Ana Uno', 'Beto Dos'], $names($admin, "course_id={$course->id}"));
        $this->assertSame(['Beto Dos'], $names($admin, "parallel_id={$b->id}"));
        $this->assertSame(['Caro Tres'], $names($admin, "course_id={$other->id}&parallel_id={$c5->id}"));
        $this->assertSame([], $names($admin, "course_id={$other->id}&parallel_id={$a->id}"));
        // El profesor filtra solo dentro de lo suyo, aunque pida el paralelo de otro curso.
        $this->assertSame(['Ana Uno'], $names($teacher, "parallel_id={$a->id}"));
        $this->assertSame([], $names($teacher, "parallel_id={$c5->id}"));
    }

    public function test_course_list_is_complete_for_admin_and_scoped_for_teacher(): void
    {
        $admin = $this->user('admin');
        $teacher = $this->user('profesor');
        $course = Course::create(['name' => '6° Primaria']);
        $other = Course::create(['name' => '5° Primaria']);
        $a = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 40]);
        Parallel::create(['name' => 'B', 'course_id' => $course->id, 'max_students' => 40]);
        Parallel::create(['name' => 'A', 'course_id' => $other->id, 'max_students' => 40]);
        Student::create(['name' => 'Ana Uno', 'course' => $course->name, 'course_id' => $course->id, 'parallel_id' => $a->id, 'teacher_user_id' => $teacher->id, 'status' => 'activo']);

        $all = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/courses')->assertOk()->json('data');
        $this->assertCount(2, $all);
        $this->assertSame(['5° Primaria', '6° Primaria'], collect($all)->pluck('name')->all());
        $this->assertSame(['A', 'B'], collect($all[1]['parallels'])->pluck('name')->all());

        $mine = $this->actingAs($teacher, 'sanctum')->getJson('/api/v1/app/courses')->assertOk()->json('data');
        $this->assertCount(1, $mine);
        $this->assertSame(['A'], collect($mine[0]['parallels'])->pluck('name')->all());

        $this->actingAs($this->user('padre'), 'sanctum')->getJson('/api/v1/app/courses')->assertForbidden();
    }

    public function test_families_and_other_roles_cannot_use_staff_student_routes(): void
    {
        $student = $this->student('Ana Paz');

        foreach (['padre', 'madre', 'tutor', 'estudiante', 'robot', 'academic'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/app/students')->assertForbidden();
            $this->actingAs($user, 'sanctum')->getJson("/api/v1/app/students/{$student->id}/summary")->assertForbidden();
        }
    }

    // ------------------------------------------------------------ robot

    public function test_teacher_lists_own_robot_activities_only(): void
    {
        $teacher = $this->user('profesor');
        $other = $this->user('profesor');
        $parallel = Parallel::create(['name' => 'A', 'course_id' => Course::create(['name' => '6°'])->id, 'max_students' => 25]);
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        foreach ([[$teacher, 'completed'], [$other, 'pending']] as [$owner, $status]) {
            RobotActivity::create([
                'id' => (string) Str::uuid(), 'teacher_user_id' => $owner->id, 'parallel_id' => $parallel->id,
                'subject_id' => $subject->id, 'quarter' => 1, 'activity_number' => 1, 'status' => $status,
                'attempt_number' => 1,
                'payload' => ['activity' => ['content' => ['category' => 'nivel1_colores'], 'groups' => [['group_id' => 'g1'], ['group_id' => 'g2']]]],
            ]);
        }

        $response = $this->actingAs($teacher, 'sanctum')->getJson('/api/v1/app/robot-activities')->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('completed', $response->json('data.0.status'));
        $this->assertSame('nivel1_colores', $response->json('data.0.category'));
        $this->assertSame(2, $response->json('data.0.groups'));

        $this->actingAs($teacher, 'sanctum')->getJson('/api/v1/app/robot-activities?status=pending')->assertOk()
            ->assertJsonCount(0, 'data');
        $this->actingAs($this->user('admin'), 'sanctum')->getJson('/api/v1/app/robot-activities')->assertForbidden();
    }

    // ------------------------------------------------------------ administrador: permisos

    public function test_teacher_and_families_cannot_use_any_admin_route(): void
    {
        $target = $this->user('padre');

        foreach (['profesor', 'padre', 'estudiante', 'academic', 'robot'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/app/admin/overview')->assertForbidden();
            $this->actingAs($user, 'sanctum')->getJson('/api/v1/app/admin/users')->assertForbidden();
            $this->actingAs($user, 'sanctum')->postJson('/api/v1/app/admin/users', $this->newUserPayload())->assertForbidden();
            $this->actingAs($user, 'sanctum')->putJson("/api/v1/app/admin/users/{$target->id}", $this->newUserPayload())->assertForbidden();
            $this->actingAs($user, 'sanctum')->patchJson("/api/v1/app/admin/users/{$target->id}/status")->assertForbidden();
            $this->actingAs($user, 'sanctum')->deleteJson("/api/v1/app/admin/users/{$target->id}")->assertForbidden();
        }
        $this->assertDatabaseMissing('users', ['email' => 'luis.rojas@school.bo']);
        $this->assertSame('active', $target->fresh()->status);
    }

    public function test_admin_token_lasts_7_days(): void
    {
        $this->user('admin', ['email' => 'dir@school.bo', 'password' => bcrypt('Clave#12345')]);

        $token = $this->postJson('/api/login', ['email' => 'dir@school.bo', 'password' => 'Clave#12345'])->assertOk()->json('access_token');

        $expires = \Laravel\Sanctum\PersonalAccessToken::findToken($token)->expires_at;
        $this->assertEqualsWithDelta(7, now()->diffInDays($expires), 1);
    }

    // ------------------------------------------------------------ administrador: panel y usuarios

    public function test_overview_counts(): void
    {
        $admin = $this->user('admin');
        $this->user('profesor');
        $this->user('padre');
        $this->user('madre', ['status' => 'inactive']);
        $this->student('Ana Paz');

        $data = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/admin/overview')->assertOk()->json('data');

        $this->assertSame(1, $data['students']);
        $this->assertSame(1, $data['teachers']);
        $this->assertSame(2, $data['families']);
        $this->assertSame(3, $data['users_active']);
        $this->assertSame(1, $data['users_suspended']);
        $this->assertSame('Ana Paz', $data['recent_students'][0]['name']);
    }

    public function test_user_list_filters_and_flags_self(): void
    {
        $admin = $this->user('admin', ['name' => 'Director General']);
        $this->user('profesor', ['name' => 'Carlos Vargas', 'email' => 'carlos@school.bo']);
        $this->user('padre', ['name' => 'Juan Quispe', 'status' => 'inactive']);
        $deleted = $this->user('madre', ['name' => 'Rosa Vega']);
        $deleted->delete();

        $all = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/admin/users')->assertOk();
        $this->assertSame(3, $all->json('meta.total'));
        $self = collect($all->json('data'))->firstWhere('name', 'Director General');
        $this->assertTrue($self['is_self']);
        $this->assertArrayNotHasKey('password', $self);

        $byRole = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/admin/users?role=profesor')->json('data');
        $this->assertSame(['Carlos Vargas'], collect($byRole)->pluck('name')->all());

        $byQ = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/admin/users?q=carlos@')->json('data');
        $this->assertSame(['Carlos Vargas'], collect($byQ)->pluck('name')->all());

        $suspended = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/admin/users?status=inactive')->json('data');
        $this->assertSame(['Juan Quispe'], collect($suspended)->pluck('name')->all());

        $trashed = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/admin/users?trashed=1')->json('data');
        $this->assertSame(['Rosa Vega'], collect($trashed)->pluck('name')->all());
        $this->assertNotNull($trashed[0]['deleted_at']);
    }

    public function test_admin_creates_user_with_web_rules_and_audit(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/app/admin/users', $this->newUserPayload(['password' => 'password1']))
            ->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/app/admin/users', $this->newUserPayload(['first_name' => 'Luis123']))
            ->assertStatus(422)->assertJsonValidationErrors(['first_name']);
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/app/admin/users', $this->newUserPayload(['rol' => 'robot']))
            ->assertStatus(422)->assertJsonValidationErrors(['rol']);

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/app/admin/users', $this->newUserPayload(['rol' => 'academic']))
            ->assertCreated()->assertJsonPath('data.role', 'academic')->assertJsonPath('data.name', 'Rojas Vega Luis');

        $this->assertTrue(AuditLog::where('action', 'user.create')->where('entity_id', $created->json('data.id'))->exists());
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/app/admin/users', $this->newUserPayload())
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_admin_updates_user_and_role_change_closes_sessions(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('padre', ['email' => 'padre@school.bo']);
        $target->createToken('app');

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/app/admin/users/{$target->id}", $this->newUserPayload([
            'email' => 'padre@school.bo', 'telefono' => '70099999', 'password' => null,
        ]))->assertOk()->assertJsonPath('data.telefono', '70099999');
        $this->assertSame(1, $target->tokens()->count(), 'editar contacto no debe cerrar sesiones');

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/app/admin/users/{$target->id}", $this->newUserPayload([
            'email' => 'padre@school.bo', 'rol' => 'tutor',
        ]))->assertOk()->assertJsonPath('data.role', 'tutor');
        $this->assertSame(0, $target->tokens()->count());
        $this->assertTrue(AuditLog::where('action', 'user.update')->where('entity_id', $target->id)->exists());
    }

    public function test_admin_cannot_change_own_role_or_suspend_or_delete_self(): void
    {
        $admin = $this->user('admin', ['email' => 'dir@school.bo']);

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/app/admin/users/{$admin->id}", $this->newUserPayload([
            'email' => 'dir@school.bo', 'rol' => 'profesor',
        ]))->assertStatus(422);
        $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/app/admin/users/{$admin->id}/status")->assertStatus(422);
        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/app/admin/users/{$admin->id}")->assertStatus(422);

        $fresh = $admin->fresh();
        $this->assertSame('admin', $fresh->role);
        $this->assertSame('active', $fresh->status);
        $this->assertNull($fresh->deleted_at);
    }

    public function test_admin_suspends_deletes_and_restores_another_user(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('profesor');
        $target->createToken('app');

        $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/app/admin/users/{$target->id}/status")
            ->assertOk()->assertJsonPath('data.status', 'inactive');
        $this->assertSame(0, $target->tokens()->count());
        $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/app/admin/users/{$target->id}/status")
            ->assertOk()->assertJsonPath('data.status', 'active');

        $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/app/admin/users/{$target->id}")->assertNoContent();
        $this->assertSoftDeleted('users', ['id' => $target->id]);

        $this->actingAs($admin, 'sanctum')->postJson("/api/v1/app/admin/users/{$target->id}/restore")
            ->assertOk()->assertJsonPath('data.deleted_at', null);
        $this->assertNotSoftDeleted('users', ['id' => $target->id]);
        $this->assertTrue(AuditLog::where('action', 'user.restore')->where('entity_id', $target->id)->exists());
    }

    public function test_validation_errors_are_in_spanish(): void
    {
        $admin = $this->user('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/app/admin/users', $this->newUserPayload(['first_name' => '', 'password' => 'abc']))
            ->assertStatus(422);

        $this->assertSame('Este campo es obligatorio.', $response->json('errors.first_name.0'));
        $this->assertStringNotContainsString('field is', json_encode($response->json('errors')));
    }

    public function test_status_is_always_reported_as_active_or_inactive(): void
    {
        $admin = $this->user('admin');
        $spanish = $this->user('profesor', ['status' => 'activo']);

        $item = collect($this->actingAs($admin, 'sanctum')->getJson('/api/v1/app/admin/users?role=profesor')->json('data'))
            ->firstWhere('id', $spanish->id);

        $this->assertSame('active', $item['status']);
    }

    public function test_deleted_user_cannot_be_edited_or_suspended_until_restored(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('profesor', ['email' => 'prof@school.bo']);
        $target->delete();

        $this->actingAs($admin, 'sanctum')->putJson("/api/v1/app/admin/users/{$target->id}", $this->newUserPayload(['email' => 'prof@school.bo']))->assertNotFound();
        $this->actingAs($admin, 'sanctum')->patchJson("/api/v1/app/admin/users/{$target->id}/status")->assertNotFound();
        $this->actingAs($admin, 'sanctum')->getJson("/api/v1/app/admin/users/{$target->id}")->assertOk()
            ->assertJsonPath('data.is_self', false);
    }

    public function test_unknown_user_returns_404(): void
    {
        $this->actingAs($this->user('admin'), 'sanctum')->getJson('/api/v1/app/admin/users/99999')->assertNotFound();
    }
}
