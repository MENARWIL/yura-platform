<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Baja de estudiantes (transferencia o retiro): solo el administrador, sin borrar nada. */
class StudentWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private Subject $subject;
    private Parallel $parallel;
    private Student $ana;
    private Student $luis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        $this->teacher = User::factory()->create(['rol' => 'profesor', 'estado' => 'activo']);
        $this->subject = Subject::where('code', 'QUE')->firstOrFail();
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $this->parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 1]);
        TeachingAssignment::create(['teacher_user_id' => $this->teacher->id, 'subject_id' => $this->subject->id, 'parallel_id' => $this->parallel->id, 'active' => true]);
        $make = fn ($last, $first) => Student::create([
            'last_name' => $last, 'first_name' => $first, 'course' => $course->name,
            'course_id' => $course->id, 'parallel_id' => $this->parallel->id, 'status' => 'activo',
        ]);
        $this->ana = $make('Quispe', 'Ana');
        $this->luis = $make('Mamani', 'Luis');
        Grade::create(['student_id' => $this->ana->id, 'subject_id' => $this->subject->id, 'type' => 'activity', 'activity_number' => 1, 'quarter' => 1, 'score' => 85, 'status' => 'completed']);
    }

    private function withdrawAna(array $over = [])
    {
        return $this->actingAs($this->admin)->post(route('students.withdraw', $this->ana), $over + [
            'motive' => 'transferencia', 'destination' => 'U.E. Simón Bolívar', 'detail' => 'Se muda a El Alto',
            'withdrawn_on' => now()->toDateString(),
        ]);
    }

    /** El aviso «… quedó transferido» se muestra una sola vez; se descarta antes de mirar las listas. */
    private function forgetNotice(): void
    {
        $this->flushSession();
    }

    public function test_admin_transfers_a_student_who_is_hidden_but_kept_with_a_backup(): void
    {
        $this->withdrawAna()->assertRedirect(route('grades.show', $this->ana))->assertSessionHas('success');

        $this->ana->refresh();
        $this->assertSame(Student::STATUS_WITHDRAWN, $this->ana->status);
        $this->assertDatabaseHas('students', ['id' => $this->ana->id]); // no se borra
        $baja = StudentWithdrawal::firstOrFail();
        $this->assertSame('U.E. Simón Bolívar', $baja->destination);
        $this->assertSame('Quispe Ana', $baja->snapshot['student']['name']);
        $this->assertEquals(85, $baja->snapshot['grades'][0]['score']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student_withdrawn', 'entity_id' => $this->ana->id]);
        $this->assertSame('Transferencia a otra unidad educativa. Se muda a El Alto', $baja->reason);
        $this->forgetNotice();

        // Desaparece de las listas, pero el administrador lo encuentra en «Dados de baja».
        $this->actingAs($this->admin)->get('/students')->assertOk()->assertDontSee('Quispe')->assertSee('Mamani');
        $this->actingAs($this->admin)->get('/students?estado=baja')->assertOk()->assertSee('Quispe Ana')->assertSee('Transferido')->assertDontSee('Mamani');
        $this->actingAs($this->admin)->get(route('grades.show', $this->ana))->assertOk()->assertSee('Reincorporar');
    }

    public function test_withdrawn_student_leaves_teacher_lists_and_cannot_receive_records(): void
    {
        $this->withdrawAna();
        $this->forgetNotice();
        $filters = "subject_id={$this->subject->id}&parallel_id={$this->parallel->id}";

        $this->actingAs($this->teacher)->get("/attendance?{$filters}")->assertOk()->assertDontSee('Quispe Ana')->assertSee('Mamani Luis');
        $this->actingAs($this->teacher)->get("/grades?{$filters}")->assertOk()->assertDontSee('Quispe Ana');
        $this->actingAs($this->teacher, 'sanctum')->getJson("/api/v1/app/teacher/attendance?{$filters}")
            ->assertOk()->assertJsonCount(1, 'data.students');

        $this->actingAs($this->teacher)->postJson('/attendance/save-list', [
            'subject_id' => $this->subject->id, 'date' => now()->toDateString(),
            'records' => [['student_id' => $this->ana->id, 'status' => 'Presente']],
        ])->assertForbidden();
        $this->assertSame(0, Attendance::count());
    }

    public function test_withdrawn_student_frees_the_place_in_the_parallel(): void
    {
        // Paralelo con cupo 1 y 2 estudiantes: con la baja, el conteo de cupo baja a 1.
        $this->assertSame(2, $this->parallel->students()->count());
        $this->withdrawAna();
        $this->assertSame(1, $this->parallel->students()->active()->count());
    }

    public function test_admin_can_reinstate_and_history_is_kept(): void
    {
        $this->withdrawAna();
        $this->actingAs($this->admin)->delete(route('students.reinstate', $this->ana))->assertRedirect(route('grades.show', $this->ana));
        $this->forgetNotice();

        $this->assertSame('activo', $this->ana->fresh()->status);
        $this->assertNotNull(StudentWithdrawal::firstOrFail()->reverted_at);
        $this->actingAs($this->admin)->get('/students')->assertSee('Quispe');
    }

    public function test_only_the_admin_can_withdraw_and_a_transfer_needs_a_destination(): void
    {
        // El Panel Administrativo (academic) sí da de baja: ver SecretaryRoleTest.
        foreach ([$this->teacher] as $user) {
            $this->actingAs($user)->post(route('students.withdraw', $this->ana), ['motive' => 'abandono', 'withdrawn_on' => now()->toDateString()])->assertRedirect();
            $this->assertSame('activo', $this->ana->fresh()->status);
        }

        $this->withdrawAna(['destination' => ''])->assertSessionHasErrors('destination', null, 'withdrawal');
        $this->withdrawAna(['motive' => 'abandono', 'destination' => '', 'withdrawn_on' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors('withdrawn_on', null, 'withdrawal');
        // «Otro» necesita que se escriba el motivo.
        $this->withdrawAna(['motive' => 'otro', 'detail' => ''])->assertSessionHasErrors('detail', null, 'withdrawal');
        $this->assertSame('activo', $this->ana->fresh()->status);
    }
}
