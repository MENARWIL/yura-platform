<?php

namespace Tests\Feature\Public;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\RobotActivity;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\PublicStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Cifras públicas de la portada: solo conteos agregados, en caché y con umbral mínimo. */
class PublicStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['yura.stats_min' => 10]);
    }

    private function student(string $name = 'Estudiante Uno', string $status = 'activo'): Student
    {
        $course = Course::firstOrCreate(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::firstOrCreate(['course_id' => $course->id, 'name' => 'A'], ['max_students' => 60]);

        return Student::create([
            'name' => $name,
            'course' => $course->name,
            'course_id' => $course->id,
            'parallel_id' => $parallel->id,
            'status' => $status,
        ]);
    }

    private function students(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->student("Estudiante {$i}");
        }
    }

    private function subject(): Subject
    {
        return Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
    }

    private function grade(Student $student, string $status = 'completed', ?int $yearId = null): Grade
    {
        return Grade::create(array_filter([
            'student_id' => $student->id,
            'subject_id' => $this->subject()->id,
            'score' => 80,
            'type' => 'activity',
            'status' => $status,
            'quarter' => 1,
            'academic_year_id' => $yearId,
        ], fn ($value) => $value !== null));
    }

    private function activity(string $status = 'completed', ?int $yearId = null): RobotActivity
    {
        $teacher = User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        $student = $this->student('Alumno de la actividad ' . Str::random(4));

        return RobotActivity::create(array_filter([
            'id' => (string) Str::uuid(),
            'teacher_user_id' => $teacher->id,
            'parallel_id' => $student->parallel_id,
            'subject_id' => $this->subject()->id,
            'quarter' => 1,
            'activity_number' => 1,
            'status' => $status,
            'payload' => ['contract_version' => '1.2'],
            'academic_year_id' => $yearId,
        ], fn ($value) => $value !== null));
    }

    public function test_summary_returns_only_aggregate_counts(): void
    {
        $summary = PublicStats::summary();

        $this->assertSame(['students', 'teachers', 'activities', 'grades', 'show'], array_keys($summary));
        $this->assertSame(0, $summary['students']);
        $this->assertSame(0, $summary['teachers']);
        $this->assertSame(0, $summary['activities']);
        $this->assertSame(0, $summary['grades']);
        $this->assertFalse($summary['show']);
    }

    public function test_it_counts_active_students_and_teachers_but_not_deleted_or_inactive_ones(): void
    {
        $this->student('Activa', 'activo');
        $this->student('Activo en inglés', 'active');
        $this->student('Inactivo', 'inactivo');
        $this->student('Eliminado')->delete();

        User::factory()->create(['role' => 'profesor', 'status' => 'activo']);
        User::factory()->create(['role' => 'profesor', 'status' => 'active']);
        User::factory()->create(['role' => 'profesor', 'status' => 'inactivo']);
        User::factory()->create(['role' => 'profesor', 'status' => 'activo'])->delete();
        User::factory()->create(['role' => 'padre', 'status' => 'activo']);
        User::factory()->create(['role' => 'admin', 'status' => 'activo']);

        $summary = PublicStats::summary();

        $this->assertSame(2, $summary['students']);
        $this->assertSame(2, $summary['teachers']);
    }

    public function test_it_counts_completed_activities_and_grades_across_all_years(): void
    {
        $old = AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);

        $this->activity('completed');
        $this->activity('pending');
        $this->activity('failed');
        // Una gestión pasada también suma: es el acumulado del proyecto.
        $this->activity('completed', $old->id);

        $kept = $this->student('Con notas');
        $this->grade($kept, 'completed');
        $this->grade($kept, 'completed', $old->id);
        $this->grade($kept, 'pending');
        // Las notas de un estudiante eliminado no se cuentan.
        $this->grade($this->student('Eliminado con notas'), 'completed');
        Student::where('name', 'Eliminado con notas')->first()->delete();

        $summary = PublicStats::summary();

        $this->assertSame(2, $summary['activities']);
        $this->assertSame(2, $summary['grades']);
    }

    public function test_a_year_filter_in_the_request_does_not_change_the_cached_figures(): void
    {
        $old = AcademicYear::create(['year' => 2025, 'name' => 'Gestión 2025', 'is_current' => false]);
        $student = $this->student();
        $this->grade($student, 'completed');
        $this->grade($student, 'completed', $old->id);

        $this->get('/?year_id=' . $old->id)->assertOk();

        $this->assertSame(2, PublicStats::summary()['grades']);
    }

    public function test_show_depends_on_the_minimum_of_students(): void
    {
        $this->students(9);
        $this->assertFalse(PublicStats::summary()['show'], 'con 9 estudiantes no se muestran cifras');

        $this->student('Estudiante 10');
        Cache::flush();
        $summary = PublicStats::summary();
        $this->assertSame(10, $summary['students']);
        $this->assertTrue($summary['show'], 'con 10 estudiantes ya se muestran');
    }

    public function test_the_minimum_comes_from_config_and_is_applied_on_every_read(): void
    {
        $this->students(3);

        $this->assertFalse(PublicStats::summary()['show']);

        // El umbral no está guardado en caché: cambiarlo tiene efecto de inmediato.
        config(['yura.stats_min' => 3]);
        $this->assertTrue(PublicStats::summary()['show']);

        // Un valor vacío o inválido vuelve al mínimo por defecto (10).
        config(['yura.stats_min' => null]);
        $this->assertFalse(PublicStats::summary()['show']);
    }

    public function test_counts_are_cached_for_ten_minutes(): void
    {
        $this->student('Primero');
        $this->assertSame(1, PublicStats::summary()['students']);

        $this->student('Segundo');
        $this->assertSame(1, PublicStats::summary()['students'], 'la segunda lectura sale de la caché');

        $this->travel(11)->minutes();
        $this->assertSame(2, PublicStats::summary()['students'], 'pasados 10 minutos se recalcula');
    }

    public function test_flush_clears_the_cache(): void
    {
        $this->student('Primero');
        PublicStats::summary();
        $this->student('Segundo');

        PublicStats::flush();

        $this->assertSame(2, PublicStats::summary()['students']);
    }

    public function test_a_failing_table_gives_zero_without_throwing(): void
    {
        $this->students(2);
        Schema::drop('robot_activities');

        $summary = PublicStats::summary();

        $this->assertSame(0, $summary['activities']);
        $this->assertSame(2, $summary['students']);
        $this->assertFalse($summary['show']);
    }

    public function test_every_table_failing_still_returns_zeros(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::drop('grades');
        Schema::drop('robot_activities');
        Schema::drop('students');
        Schema::enableForeignKeyConstraints();

        $this->assertSame(
            ['students' => 0, 'teachers' => 0, 'activities' => 0, 'grades' => 0, 'show' => false],
            PublicStats::summary()
        );
    }
}
