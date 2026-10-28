<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeachingAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Correcciones de la revisión QA de la web: límites de notas, asistencia de un clic
 * (con licencia y registro de lista), panel con la asistencia del día, buscador y paginación.
 */
class QaWebImprovementsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Subject $subject;
    private Course $course;
    private Parallel $parallel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['rol' => 'profesor', 'estado' => 'activo']);
        $this->subject = Subject::where('code', 'QUE')->firstOrFail();
        $this->course = Course::create(['name' => 'Sexto de Primaria']);
        $this->parallel = Parallel::create(['name' => 'A', 'course_id' => $this->course->id, 'max_students' => 40]);

        TeachingAssignment::create([
            'teacher_user_id' => $this->teacher->id,
            'subject_id' => $this->subject->id,
            'parallel_id' => $this->parallel->id,
            'active' => true,
        ]);
    }

    private function student(string $name, array $extra = []): Student
    {
        return Student::create($extra + [
            'name' => $name,
            'course' => $this->course->name,
            'course_id' => $this->course->id,
            'parallel_id' => $this->parallel->id,
            'teacher_user_id' => $this->teacher->id,
            'age' => 11,
            'gender' => 'Femenino',
            'level' => 'básico',
            'registration_date' => '2026-05-25',
        ]);
    }

    private function saveGrade(Student $student, $score, string $type = 'activity')
    {
        return $this->actingAs($this->teacher)->postJson('/grades/save', [
            'student_id' => $student->id,
            'subject_id' => $this->subject->id,
            'type' => $type,
            'activity_number' => 1,
            'quarter' => 1,
            'score' => $score,
        ]);
    }

    public function test_grade_must_be_an_integer_between_1_and_100(): void
    {
        $student = $this->student('Ana Quispe');

        foreach ([345, 101, -5, 0, 87.5] as $invalid) {
            $this->saveGrade($student, $invalid)->assertStatus(422);
        }
        $this->assertDatabaseCount('grades', 0);

        $this->saveGrade($student, 1)->assertOk()->assertJson(['success' => true]);
        $this->saveGrade($student, 100)->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('grades', ['student_id' => $student->id, 'score' => 100]);
    }

    public function test_self_assessment_is_limited_to_5(): void
    {
        $student = $this->student('Ana Quispe');

        $this->saveGrade($student, 6, 'autoeval')->assertStatus(422);
        $this->saveGrade($student, 5, 'autoeval')->assertOk()->assertJson(['success' => true]);
    }

    public function test_grade_notebook_no_longer_has_the_redundant_save_all_button(): void
    {
        $this->student('Ana Quispe');

        $this->actingAs($this->teacher)
            ->get('/grades?subject_id=' . $this->subject->id . '&parallel_id=' . $this->parallel->id)
            ->assertOk()
            ->assertDontSee('save-all-grades')
            ->assertSee('data-filter-required', false);
    }

    public function test_attendance_accepts_licence_and_rejects_future_dates(): void
    {
        $student = $this->student('Ana Quispe');
        $payload = ['student_id' => $student->id, 'subject_id' => $this->subject->id, 'status' => 'Licencia'];

        $this->actingAs($this->teacher)
            ->postJson('/attendance/save', $payload + ['date' => now()->toDateString()])
            ->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('attendance', ['student_id' => $student->id, 'status' => 'Licencia']);

        $this->actingAs($this->teacher)
            ->postJson('/attendance/save', $payload + ['date' => now()->addDay()->toDateString()])
            ->assertStatus(422);
    }

    public function test_attendance_screen_starts_everyone_as_present_without_saving(): void
    {
        $this->student('Ana Quispe');
        $this->student('Luis Mamani');

        $this->actingAs($this->teacher)
            ->get('/attendance?subject_id=' . $this->subject->id . '&parallel_id=' . $this->parallel->id)
            ->assertOk()
            ->assertSee('Registrar asistencia')
            ->assertSee('2 sin registrar');

        // Abrir la pantalla no crea registros: solo se guardan al registrar la lista.
        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_teacher_registers_the_whole_list_in_one_request(): void
    {
        $ana = $this->student('Ana Quispe');
        $luis = $this->student('Luis Mamani');
        $date = now()->toDateString();

        $this->actingAs($this->teacher)->postJson('/attendance/save-list', [
            'subject_id' => $this->subject->id,
            'date' => $date,
            'records' => [
                ['student_id' => $ana->id, 'status' => 'Presente'],
                ['student_id' => $luis->id, 'status' => 'Falta'],
            ],
        ])->assertOk()->assertJson(['success' => true, 'saved' => 2]);

        $this->assertDatabaseHas('attendance', ['student_id' => $ana->id, 'status' => 'Presente']);
        $this->assertDatabaseHas('attendance', ['student_id' => $luis->id, 'status' => 'Falta']);
    }

    public function test_teacher_cannot_register_list_for_students_outside_their_assignment(): void
    {
        $otherTeacher = User::factory()->create(['rol' => 'profesor', 'estado' => 'activo']);
        $otherParallel = Parallel::create(['name' => 'B', 'course_id' => $this->course->id, 'max_students' => 40]);
        $foreign = $this->student('Ajeno', ['teacher_user_id' => $otherTeacher->id, 'parallel_id' => $otherParallel->id]);

        $this->actingAs($this->teacher)->postJson('/attendance/save-list', [
            'subject_id' => $this->subject->id,
            'date' => now()->toDateString(),
            'records' => [['student_id' => $foreign->id, 'status' => 'Presente']],
        ])->assertForbidden();

        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_licence_is_excluded_from_the_attendance_percentage(): void
    {
        $records = collect(['Presente', 'Falta', 'Licencia', 'Presente'])
            ->map(fn ($status) => new Attendance(['status' => $status]));

        $this->assertSame(66.67, Attendance::presentPercent($records));
        $this->assertNull(Attendance::presentPercent([new Attendance(['status' => 'Licencia'])]));
    }

    public function test_teacher_dashboard_shows_todays_attendance(): void
    {
        $ana = $this->student('Ana Quispe');
        $luis = $this->student('Luis Mamani');
        foreach ([[$ana, 'Presente'], [$luis, 'Falta']] as [$student, $status]) {
            Attendance::create([
                'student_id' => $student->id,
                'subject_id' => $this->subject->id,
                'date' => now()->toDateString(),
                'status' => $status,
            ]);
        }

        $response = $this->actingAs($this->teacher)->get('/dashboard')->assertOk();

        // Antes siempre mostraba 0% porque comparaba una fecha (Carbon) con texto.
        $this->assertEquals(50, $response->viewData('data')['asistencia_hoy']);
    }

    public function test_teacher_sees_students_of_assigned_parallel_without_direct_assignment(): void
    {
        // Caso real de la base local: estudiantes sin teacher_user_id, ligados al profesor
        // solo por la asignación docente del paralelo.
        $this->student('Ana Quispe', ['teacher_user_id' => null, 'first_name' => 'Ana', 'last_name' => 'Quispe']);
        $otherParallel = Parallel::create(['name' => 'B', 'course_id' => $this->course->id, 'max_students' => 40]);
        $this->student('Fuera Delparalelo', ['teacher_user_id' => null, 'parallel_id' => $otherParallel->id]);
        $filters = '?subject_id=' . $this->subject->id . '&parallel_id=' . $this->parallel->id;

        $this->actingAs($this->teacher)->get('/attendance' . $filters)
            ->assertOk()->assertSee('Ana Quispe')->assertDontSee('Fuera Delparalelo');
        $this->actingAs($this->teacher)->get('/grades' . $filters)
            ->assertOk()->assertSee('Ana Quispe')->assertDontSee('Fuera Delparalelo');
        $this->actingAs($this->teacher)->get('/students')
            ->assertOk()->assertSee('Quispe')->assertDontSee('Delparalelo');

        $response = $this->actingAs($this->teacher)->get('/dashboard')->assertOk();
        $this->assertSame(1, $response->viewData('data')['mis_estudiantes_count']);
        // Sin notas no hay estudiantes "en bajo rendimiento".
        $this->assertSame(0, $response->viewData('data')['bajo_rendimiento']);
    }

    public function test_teacher_student_filters_only_offer_assigned_courses_and_parallels(): void
    {
        $this->student('Ana Quispe', ['teacher_user_id' => null]);
        $otherCourse = Course::create(['name' => 'Quinto de Primaria']);
        Parallel::create(['name' => 'Z', 'course_id' => $otherCourse->id, 'max_students' => 40]);
        Parallel::create(['name' => 'Y', 'course_id' => $this->course->id, 'max_students' => 40]);

        $response = $this->actingAs($this->teacher)->get('/students')->assertOk();

        $this->assertSame([$this->course->id], $response->viewData('courses')->pluck('id')->all());
        $this->assertSame([$this->parallel->id], $response->viewData('parallels')->pluck('id')->all());
    }

    public function test_assigned_teacher_can_open_student_record_but_not_edit_it(): void
    {
        $student = $this->student('Ana Quispe', ['teacher_user_id' => null]);
        $otherParallel = Parallel::create(['name' => 'B', 'course_id' => $this->course->id, 'max_students' => 40]);
        $foreign = $this->student('Fuera Delparalelo', ['teacher_user_id' => null, 'parallel_id' => $otherParallel->id]);

        $this->assertTrue($this->teacher->teachesStudent($student));
        $this->assertFalse($this->teacher->teachesStudent($foreign));

        $this->actingAs($this->teacher)->get(route('grades.show', $student))->assertOk();
        $this->actingAs($this->teacher)->get(route('students.show', $student))->assertOk();
        $this->actingAs($this->teacher)->get(route('students.show', $foreign))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('students.edit', $student))->assertForbidden();
    }

    public function test_mobile_api_lists_students_of_assigned_parallel(): void
    {
        $student = $this->student('Ana Quispe', ['teacher_user_id' => null]);
        $otherParallel = Parallel::create(['name' => 'B', 'course_id' => $this->course->id, 'max_students' => 40]);
        $this->student('Fuera Delparalelo', ['teacher_user_id' => null, 'parallel_id' => $otherParallel->id]);
        $token = $this->teacher->createToken('test')->plainTextToken;
        $auth = ['Authorization' => 'Bearer ' . $token];

        $ids = collect($this->getJson('/api/students', $auth)->assertOk()->json('data'))->pluck('id');
        $this->assertSame([$student->id], $ids->all());

        $this->getJson('/api/v1/app/students', $auth)->assertOk()->assertJsonFragment(['id' => $student->id])
            ->assertJsonMissing(['name' => 'Fuera Delparalelo']);
        $this->assertSame(1, $this->getJson('/api/dashboard', $auth)->assertOk()->json('metrics.mis_estudiantes_count'));
        $this->getJson('/api/students/' . $student->id, $auth)->assertOk();
    }

    public function test_dashboard_assigned_students_table_shows_five_per_page(): void
    {
        foreach (range(1, 7) as $i) {
            $this->student("Estudiante {$i}", ['teacher_user_id' => null, 'first_name' => "Nombre{$i}", 'last_name' => sprintf('Apellido%02d', $i)]);
        }

        $response = $this->actingAs($this->teacher)->get('/dashboard')->assertOk();
        $data = $response->viewData('data');
        $this->assertSame(7, $data['mis_estudiantes_count']);
        $this->assertCount(5, $data['mis_estudiantes']);
        $response->assertSee('Mostrando 1–5 de 7 estudiantes');

        $page2 = $this->actingAs($this->teacher)->get('/dashboard?estudiantes=2')->viewData('data')['mis_estudiantes'];
        $this->assertSame(['Estudiante 6', 'Estudiante 7'], collect($page2->items())->pluck('name')->all());
    }

    public function test_attendance_pdf_exports_a_date_range_chosen_by_the_teacher(): void
    {
        $ana = $this->student('Ana Quispe', ['teacher_user_id' => null]);
        $luis = $this->student('Luis Mamani', ['teacher_user_id' => null]);
        $records = [
            [$ana, '2026-10-05', 'Presente'], [$luis, '2026-10-05', 'Falta'],
            [$ana, '2026-10-06', 'Licencia'], [$luis, '2026-10-06', 'Presente'],
            [$ana, '2026-09-20', 'Falta'], // fuera del rango
        ];
        foreach ($records as [$student, $date, $status]) {
            Attendance::create(['student_id' => $student->id, 'subject_id' => $this->subject->id, 'date' => $date, 'status' => $status]);
        }
        $base = '/attendance/export-pdf?subject_id=' . $this->subject->id . '&parallel_id=' . $this->parallel->id;

        $this->actingAs($this->teacher)->get($base . '&date_from=2026-10-01&date_to=2026-10-10')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('asistencia_20261001_a_20261010.pdf');

        // Un solo día con el parámetro de siempre sigue funcionando.
        $this->actingAs($this->teacher)->get($base . '&date=2026-10-05')
            ->assertOk()->assertDownload('asistencia_20261005.pdf');

        $this->actingAs($this->teacher)->get($base . '&date_from=2026-10-10&date_to=2026-10-01')
            ->assertSessionHasErrors('date_to');
        $this->actingAs($this->teacher)->get($base . '&date_from=2026-10-01&date_to=' . now()->addDay()->toDateString())
            ->assertSessionHasErrors('date_to');
    }

    public function test_attendance_pdf_range_counts_per_student(): void
    {
        $ana = $this->student('Ana Quispe', ['teacher_user_id' => null]);
        foreach ([['2026-10-05', 'Presente'], ['2026-10-06', 'Falta'], ['2026-10-07', 'Licencia'], ['2026-09-01', 'Falta']] as [$date, $status]) {
            Attendance::create(['student_id' => $ana->id, 'subject_id' => $this->subject->id, 'date' => $date, 'status' => $status]);
        }

        $html = view('reports.attendance_pdf', $this->pdfData($ana, '2026-10-01', '2026-10-10'))->render();

        $this->assertStringContainsString('Periodo:', $html);
        $this->assertStringContainsString('01/10/2026 al 10/10/2026', $html);
        $this->assertStringContainsString('05/10', $html);
        $this->assertStringNotContainsString('01/09', $html);
        // 1 presente y 1 falta (la licencia no cuenta): 50 %.
        $this->assertStringContainsString('50%', $html);
    }

    private function pdfData(Student $student, string $from, string $to): array
    {
        $this->actingAs($this->teacher);
        $records = Attendance::where('student_id', $student->id)->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->orderBy('date')->get();
        $days = $records->map(fn ($r) => $r->date->toDateString())->unique()->values();

        return [
            'students' => collect([$student]),
            'subject' => $this->subject,
            'parallel' => $this->parallel,
            'dateFrom' => \Illuminate\Support\Carbon::parse($from),
            'dateTo' => \Illuminate\Support\Carbon::parse($to),
            'days' => $days,
            'grid' => [$student->id => $records->mapWithKeys(fn ($r) => [$r->date->toDateString() => $r->status])->all()],
            'totals' => collect([$student->id => [
                'Presente' => 1, 'Falta' => 1, 'Atraso' => 0, 'Licencia' => 1,
                'percent' => Attendance::presentPercent($records, 1),
            ]]),
            'showDays' => true,
        ];
    }

    public function test_student_record_shows_teacher_from_teaching_assignment(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        $student = $this->student('Ana Quispe', ['teacher_user_id' => null]);
        $otherParallel = Parallel::create(['name' => 'B', 'course_id' => $this->course->id, 'max_students' => 40]);
        $alone = $this->student('Sin Profesor', ['teacher_user_id' => null, 'parallel_id' => $otherParallel->id]);

        $this->assertSame($this->teacher->name . ' (' . $this->subject->name . ')', $student->teachersLabel());
        $this->assertNull($alone->teachersLabel());

        $this->actingAs($admin)->get(route('grades.show', $student))
            ->assertOk()->assertSee($this->teacher->name);
    }

    public function test_family_relations_are_paginated_and_searchable_by_surname_or_tutor(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        foreach (range(1, 6) as $i) {
            $tutor = User::factory()->create(['rol' => 'tutor', 'estado' => 'activo', 'name' => "Tutor Numero{$i}", 'last_name' => "Tutor{$i}"]);
            $student = $this->student("Estudiante {$i}", ['first_name' => "Nombre{$i}", 'last_name' => "Apellido{$i}"]);
            \App\Models\StudentFamilyMember::create([
                'student_id' => $student->id, 'user_id' => $tutor->id, 'relation_type' => 'tutor',
                'is_primary' => true, 'active' => true, 'can_view' => true,
            ]);
        }

        $this->actingAs($admin)->get('/family-members')
            ->assertOk()->assertSee('Mostrando 1–5 de 6 relaciones');

        $this->actingAs($admin)->get('/family-members?student_name=Apellido3')
            ->assertOk()->assertSee('Estudiante 3')->assertDontSee('Estudiante 4');

        $this->actingAs($admin)->get('/family-members?student_name=Numero5')
            ->assertOk()->assertSee('Estudiante 5')->assertDontSee('Estudiante 2');
    }

    public function test_user_list_is_paginated_and_searchable_on_the_server(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        foreach (range(1, 6) as $i) {
            User::factory()->create(['rol' => 'tutor', 'estado' => 'activo', 'name' => "Persona Tutor{$i}", 'last_name' => sprintf('Zeta%02d', $i), 'email' => "tutor{$i}@example.test"]);
        }

        $response = $this->actingAs($admin)->get('/users')->assertOk();
        $this->assertCount(5, $response->viewData('users'));

        // Busca en todas las páginas, no solo en la visible.
        $this->actingAs($admin)->get('/users?q=tutor6@example.test')
            ->assertOk()->assertSee('Persona Tutor6')->assertDontSee('Persona Tutor1');

        $familia = $this->actingAs($admin)->get('/users?role=familia&per_page=20')->viewData('users');
        $this->assertSame(6, $familia->total());
        $this->assertSame(1, $this->actingAs($admin)->get('/users?role=profesor')->viewData('users')->total());
    }

    public function test_clearing_the_student_search_shows_the_full_list(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        $this->student('Ana Quispe', ['first_name' => 'Ana', 'last_name' => 'Quispe']);
        $this->student('Luis Mamani', ['first_name' => 'Luis', 'last_name' => 'Mamani']);

        $this->actingAs($admin)->get('/students?student_name=')
            ->assertOk()->assertSee('Quispe')->assertSee('Mamani');

        $this->actingAs($admin)->get('/students?student_name=mamani')
            ->assertOk()->assertSee('Mamani')->assertDontSee('Quispe');
    }

    public function test_student_list_is_paginated_with_selectable_page_size(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'estado' => 'activo']);
        foreach (range(1, 7) as $i) {
            $this->student("Estudiante {$i}", ['first_name' => "Nombre{$i}", 'last_name' => sprintf('Apellido%02d', $i)]);
        }

        $this->actingAs($admin)->get('/students?per_page=5')
            ->assertOk()
            ->assertSee('Apellido05')
            ->assertDontSee('Apellido06')
            ->assertSee('Mostrando 1–5 de 7 estudiantes');

        $this->actingAs($admin)->get('/students?per_page=5&page=2')
            ->assertOk()->assertSee('Apellido07')->assertDontSee('Apellido01');

        $this->actingAs($admin)->get('/students?per_page=1000')->assertSessionHasErrors('per_page');
    }
}
