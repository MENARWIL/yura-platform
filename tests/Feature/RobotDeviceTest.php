<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\RobotActivity;
use App\Models\RobotActivityAttempt;
use App\Models\RobotDevice;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Services\RobotDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/** Contrato v1.3: el robot es un dispositivo registrado con su propia llave, no un usuario. */
class RobotDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_registers_a_robot_and_sees_the_key_only_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $quechua = Subject::where('code', 'QUE')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('admin.robot.store'), [
            'name' => 'YURA - Raspberry aula Quechua',
            'subject_id' => $quechua->id,
        ])->assertRedirect(route('admin.robot.index'));

        $key = session('robot_key');
        $this->assertStringStartsWith('yura_rbt_', $key);

        $device = RobotDevice::firstOrFail();
        $this->assertSame($quechua->id, $device->subject_id);
        $this->assertSame(substr($key, -4), $device->key_hint);

        // En la base solo queda la huella: la llave en claro no está en ninguna parte.
        $token = PersonalAccessToken::query()->firstOrFail();
        $this->assertStringNotContainsString(substr($key, 9), (string) $token->token);
        $this->assertEqualsCanonicalizing(RobotDevice::ABILITIES, $token->abilities);
        $this->assertNull($token->expires_at);

        $this->actingAs($admin)->get(route('admin.robot.index'))->assertOk()->assertSee('yura_rbt_', false);
        // Al volver a abrir la pantalla la llave ya no se muestra.
        $this->actingAs($admin)->get(route('admin.robot.index'))->assertOk()->assertDontSee($key, false);

        $this->assertDatabaseHas('audit_logs', ['action' => 'robot.create', 'entity' => 'robot_device', 'entity_id' => $device->id]);
    }

    public function test_only_admin_and_director_manage_the_key_and_the_panel_only_reads(): void
    {
        $quechua = Subject::where('code', 'QUE')->firstOrFail();
        $device = $this->robot($quechua)[0];

        foreach (['admin', 'director'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $this->actingAs($user)->post(route('admin.robot.rotate', $device))->assertRedirect(route('admin.robot.index'));
        }

        $panel = User::factory()->create(['role' => 'academic', 'status' => 'active']);
        $this->actingAs($panel)->get(route('admin.robot.index'))->assertOk()->assertSee('Solo consulta');
        $this->actingAs($panel)->post(route('admin.robot.rotate', $device))->assertForbidden();
        $this->actingAs($panel)->post(route('admin.robot.revoke', $device))->assertForbidden();
        $this->actingAs($panel)->post(route('admin.robot.store'), ['name' => 'Otro', 'subject_id' => $quechua->id])->assertForbidden();

        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $this->actingAs($teacher)->get(route('admin.robot.index'))->assertRedirect('/');
    }

    public function test_robot_claims_with_post_and_only_from_its_own_subject(): void
    {
        [$teacher, $parallel, $quechua, $students] = $this->classroom();
        $this->queueActivity($teacher, $parallel, $quechua, $students);

        $math = Subject::create(['name' => 'Matemática', 'code' => 'MAT', 'active' => true]);
        [, $mathKey] = $this->robot($math, 'Robot de matemática');
        [$device, $key] = $this->robot($quechua);

        // Otra materia: la cola de este robot está vacía.
        $this->asRobot($mathKey)->postJson('/api/v1/robot/claims')->assertNoContent();

        $claim = $this->asRobot($key)->postJson('/api/v1/robot/claims')
            ->assertCreated()
            ->assertHeader('X-Yura-Contract', '1.3')
            ->assertJsonPath('contract_version', '1.2')
            ->assertJsonPath('activity.attempt_number', 1);

        $this->assertSame($device->id, RobotActivityAttempt::firstOrFail()->robot_device_id);

        // Reclamar otra vez devuelve el mismo intento en curso.
        $this->asRobot($key)->postJson('/api/v1/robot/claims')
            ->assertCreated()
            ->assertJsonPath('activity.attempt_id', $claim->json('activity.attempt_id'));

        // El GET que modifica datos es del contrato v1.2: un robot registrado no puede usarlo.
        $this->asRobot($key)->getJson('/api/v1/robot/activities/next')->assertForbidden();
    }

    public function test_key_needs_the_right_permission_for_each_action(): void
    {
        [$teacher, $parallel, $quechua, $students] = $this->classroom();
        $activity = $this->queueActivity($teacher, $parallel, $quechua, $students);
        [$device] = $this->robot($quechua);

        $onlySend = RobotDevice::KEY_PREFIX . $device->createToken('solo-notas', [RobotDevice::ABILITY_SEND])->plainTextToken;
        $onlyReceive = RobotDevice::KEY_PREFIX . $device->createToken('solo-recibir', [RobotDevice::ABILITY_RECEIVE])->plainTextToken;

        $this->asRobot($onlySend)->postJson('/api/v1/robot/claims')->assertForbidden();
        $claim = $this->asRobot($onlyReceive)->postJson('/api/v1/robot/claims')->assertCreated();

        $this->asRobot($onlyReceive)
            ->postJson("/api/v1/robot/activities/{$activity->id}/results", $this->resultFor($activity, $claim->json('activity.attempt_id')))
            ->assertForbidden();
        $this->assertSame(0, Grade::where('robot_activity_id', $activity->id)->count());
    }

    public function test_revoked_or_renewed_key_stops_working_at_once(): void
    {
        $quechua = Subject::where('code', 'QUE')->firstOrFail();
        [$device, $key] = $this->robot($quechua);
        $service = app(RobotDeviceService::class);

        $this->asRobot($key)->getJson('/api/v1/robot/me')->assertOk();

        $this->app['auth']->forgetGuards();
        $newKey = $service->rotate($device);
        $this->app['auth']->forgetGuards();
        $this->asRobot($key)->getJson('/api/v1/robot/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->asRobot($newKey)->getJson('/api/v1/robot/me')->assertOk();

        $this->app['auth']->forgetGuards();
        $service->revoke($device);
        $this->app['auth']->forgetGuards();
        $this->asRobot($newKey)->getJson('/api/v1/robot/me')->assertUnauthorized();
        $this->assertSame('revoked', $device->fresh()->status);
        $this->assertSame(0, $device->tokens()->count());
    }

    public function test_me_reports_the_robot_and_records_its_last_use(): void
    {
        $quechua = Subject::where('code', 'QUE')->firstOrFail();
        [$device, $key] = $this->robot($quechua, 'YURA aula');

        $this->assertNull($device->last_used_at);
        $this->asRobot($key)->getJson('/api/v1/robot/me')
            ->assertOk()
            ->assertJsonPath('name', 'YURA aula')
            ->assertJsonPath('subject.name', 'Quechua')
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('abilities', RobotDevice::ABILITIES)
            ->assertJsonPath('contract', '1.3');

        $device->refresh();
        $this->assertNotNull($device->last_used_at);
        $this->assertNotNull($device->last_used_ip);
    }

    public function test_results_are_accepted_with_iso_dates_and_only_from_the_robot_that_claimed(): void
    {
        [$teacher, $parallel, $quechua, $students] = $this->classroom();
        $activity = $this->queueActivity($teacher, $parallel, $quechua, $students);
        [, $key] = $this->robot($quechua);
        [, $otherKey] = $this->robot($quechua, 'Otro robot de Quechua');

        $attemptId = $this->asRobot($key)->postJson('/api/v1/robot/claims')->assertCreated()->json('activity.attempt_id');
        $result = $this->resultFor($activity, $attemptId);

        // Otro robot, aunque sea de la misma materia, no puede entregar lo que no reclamó.
        $this->asRobot($otherKey)->postJson("/api/v1/robot/activities/{$activity->id}/results", $result)->assertForbidden();
        $this->assertSame('in_progress', $activity->fresh()->status);

        // Fechas ISO con «Z» (las que manda el robot): MySQL las rechazaba con un error 500.
        $this->asRobot($key)->postJson("/api/v1/robot/activities/{$activity->id}/results", $result)
            ->assertCreated()
            ->assertJsonPath('status', 'completed');

        $attempt = RobotActivityAttempt::firstOrFail();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame(
            \Carbon\Carbon::parse($result['started_at'])->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $attempt->started_at->format('Y-m-d H:i:s')
        );
        $this->assertSame(1, Grade::where('robot_activity_id', $activity->id)->where('type', 'robot')->count());
    }

    public function test_the_robot_role_cannot_open_a_web_session(): void
    {
        User::factory()->create([
            'email' => 'robot@yura.bo', 'role' => 'robot', 'status' => 'active', 'password' => bcrypt('Correcta#2026'),
        ]);

        $this->post('/login', ['email' => 'robot@yura.bo', 'password' => 'Correcta#2026'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_command_registers_a_robot_and_renews_it_by_name(): void
    {
        $this->artisan('robot:llave', ['nombre' => 'Robot consola', '--solo-llave' => true])->assertSuccessful();
        $device = RobotDevice::where('name', 'Robot consola')->firstOrFail();
        $first = $device->tokens()->firstOrFail()->id;

        $this->artisan('robot:llave', ['nombre' => 'Robot consola', '--solo-llave' => true])->assertSuccessful();

        $this->assertSame(1, RobotDevice::count());
        $this->assertSame(1, $device->tokens()->count());
        $this->assertNotSame($first, $device->tokens()->firstOrFail()->id);
        $this->assertSame(1, AuditLog::where('action', 'robot.rotate')->count());
    }

    // ------------------------------------------------------------------ ayudas

    /** Cada petición del robot entra con su llave: el guard de pruebas recuerda al usuario anterior. */
    private function asRobot(string $key): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($key);
    }

    /** @return array{0: RobotDevice, 1: string} el robot y su llave */
    private function robot(Subject $subject, string $name = 'YURA aula'): array
    {
        return app(RobotDeviceService::class)->register($name, $subject, null, null);
    }

    private function classroom(): array
    {
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $subject = Subject::where('code', 'QUE')->firstOrFail();
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 6]);
        $student = Student::create([
            'name' => 'Estudiante 1', 'first_name' => 'Estudiante1', 'course' => $course->name, 'course_id' => $course->id,
            'parallel_id' => $parallel->id, 'teacher_user_id' => $teacher->id, 'age' => 10, 'gender' => 'Femenino',
            'level' => 'básico', 'registration_date' => '2026-10-01',
        ]);
        TeachingAssignment::create([
            'teacher_user_id' => $teacher->id, 'subject_id' => $subject->id, 'parallel_id' => $parallel->id, 'active' => true,
        ]);

        return [$teacher, $parallel, $subject, [$student]];
    }

    private function queueActivity(User $teacher, Parallel $parallel, Subject $subject, array $students): RobotActivity
    {
        $this->actingAs($teacher)->post('/robot-activities', [
            'parallel_id' => $parallel->id,
            'subject_id' => $subject->id,
            'quarter' => 1,
            'activity_number' => 1,
            'category' => 'nivel1_colores',
            'participant_ids' => array_map(fn (Student $s) => $s->id, $students),
            'group_size' => 1,
            'respondents_per_group' => 1,
            'questions_per_respondent' => 1,
        ])->assertRedirect(route('robot-activities.index'));

        // Desde aquí habla el robot con su llave, no el profesor.
        $this->app['auth']->forgetGuards();

        return RobotActivity::firstOrFail();
    }

    private function resultFor(RobotActivity $activity, string $attemptId): array
    {
        $group = data_get($activity->payload, 'activity.groups.0');
        $respondent = $group['respondent_student_ids'][0];

        return [
            'contract_version' => '1.2',
            'activity_id' => $activity->id,
            'attempt_id' => $attemptId,
            'status' => 'completed',
            'started_at' => '2026-11-23T05:10:59Z',
            'finished_at' => '2026-11-23T05:11:21Z',
            'group_results' => [[
                'group_id' => $group['group_id'],
                'member_student_ids' => array_column($group['members'], 'student_id'),
                'respondent_student_ids' => [$respondent],
                'points_earned' => 100,
                'points_possible' => 100,
                'score_percentage' => 100,
                'responses' => [[
                    'student_id' => $respondent, 'question_id' => 'group-1-' . $respondent . '-1', 'correct' => true,
                    'points_earned' => 100, 'points_possible' => 100, 'answered_at' => '2026-11-23T05:11:16Z',
                ]],
            ]],
        ];
    }
}
