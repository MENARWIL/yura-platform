<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Grade;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Importación de listas reales. Los nombres de estas pruebas son ficticios. */
class ImportStudentListTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $body): string
    {
        $path = sys_get_temp_dir() . '/lista_' . uniqid() . '.csv';
        file_put_contents($path, "\xEF\xBB\xBF" . "curso;paralelo;paterno;materno;nombres\n" . $body);

        return $path;
    }

    private function seedTestStudent(string $name): Student
    {
        $course = Course::firstOrCreate(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::firstOrCreate(['course_id' => $course->id, 'name' => 'A'], ['max_students' => 20]);

        return Student::create(['name' => $name, 'course' => $course->name, 'course_id' => $course->id, 'parallel_id' => $parallel->id]);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $path = $this->csv("Sexto de Primaria;A;Ficticio;Uno;Ana Maria\n");

        $this->artisan('students:import', ['file' => $path])->assertSuccessful();

        $this->assertSame(0, Student::count());
    }

    public function test_apply_creates_students_with_split_names_and_a_single_surname(): void
    {
        $path = $this->csv("Sexto de Primaria;B;Ficticio;Uno;Ana Maria\nSexto de Primaria;B;;Solo;Luis Pedro\n");

        $this->artisan('students:import', ['file' => $path, '--apply' => true])->assertSuccessful();

        $ana = Student::where('first_name', 'Ana Maria')->firstOrFail();
        $this->assertSame('Ficticio Uno Ana Maria', $ana->name);
        $this->assertSame('Ficticio', $ana->last_name);
        $this->assertSame('Uno', $ana->second_last_name);
        $this->assertSame('B', $ana->parallel->name);
        $this->assertSame('activo', $ana->status);

        $luis = Student::where('first_name', 'Luis Pedro')->firstOrFail();
        $this->assertSame('Solo Luis Pedro', $luis->name);
        $this->assertNull($luis->last_name);
        $this->assertSame('Solo', $luis->second_last_name);
    }

    public function test_capacity_is_raised_so_a_full_class_fits(): void
    {
        $this->seedTestStudent('Prueba Uno - 1');
        $path = $this->csv("Sexto de Primaria;A;Ficticio;Uno;Ana\n");

        $this->artisan('students:import', ['file' => $path, '--apply' => true])->assertSuccessful();

        $this->assertGreaterThanOrEqual(40, Parallel::where('name', 'A')->firstOrFail()->max_students);
    }

    public function test_running_twice_does_not_duplicate(): void
    {
        $path = $this->csv("Sexto de Primaria;A;Ficticio;Uno;Ana\n");

        $this->artisan('students:import', ['file' => $path, '--apply' => true])->assertSuccessful();
        $this->artisan('students:import', ['file' => $path, '--apply' => true])->assertSuccessful();

        $this->assertSame(1, Student::count());
    }

    public function test_replace_test_deletes_only_seeded_test_students_and_their_data(): void
    {
        $test = $this->seedTestStudent('Prueba Uno - 1');
        $keep = $this->seedTestStudent('Alumno Real Existente');
        $subject = Subject::firstOrCreate(['code' => 'QUE'], ['name' => 'Quechua']);
        Grade::create(['student_id' => $test->id, 'subject_id' => $subject->id, 'score' => 50, 'type' => 'activity', 'status' => 'completed', 'quarter' => 1]);
        $path = $this->csv("Sexto de Primaria;A;Ficticio;Uno;Ana\n");

        $this->artisan('students:import', ['file' => $path, '--apply' => true, '--replace-test' => true])->assertSuccessful();

        $this->assertNull(Student::withTrashed()->find($test->id), 'el estudiante de prueba debía borrarse');
        $this->assertSame(0, Grade::where('student_id', $test->id)->count(), 'sus notas debían borrarse');
        $this->assertNotNull(Student::find($keep->id), 'un estudiante que no es de prueba no se toca');
        $this->assertSame(2, Student::count());
    }

    public function test_audit_log_has_counts_but_no_names(): void
    {
        $path = $this->csv("Sexto de Primaria;A;Ficticio;Uno;Ana\n");

        $this->artisan('students:import', ['file' => $path, '--apply' => true])->assertSuccessful();

        $log = AuditLog::where('action', 'students.import')->firstOrFail();
        $this->assertSame(1, $log->details['created']);
        $this->assertStringNotContainsString('Ficticio', json_encode($log->details));
    }

    public function test_rejects_bad_header_and_incomplete_rows(): void
    {
        $bad = sys_get_temp_dir() . '/lista_mala.csv';
        file_put_contents($bad, "a;b;c\n1;2;3\n");
        $this->artisan('students:import', ['file' => $bad, '--apply' => true])->assertFailed();

        $incomplete = $this->csv("Sexto de Primaria;A;;;Ana\n");
        $this->artisan('students:import', ['file' => $incomplete, '--apply' => true])->assertFailed();

        $this->assertSame(0, Student::count());
    }
}
