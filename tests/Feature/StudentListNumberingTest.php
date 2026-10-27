<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En el cuaderno de notas y en asistencia, la primera columna es "Nro": la posición del
 * estudiante en la lista alfabética de su curso y paralelo, no el id de la base de datos.
 */
class StudentListNumberingTest extends TestCase
{
    use RefreshDatabase;

    private function seedList(): array
    {
        $course = Course::create(['name' => 'Sexto de Primaria', 'nivel_educativo' => 'primaria']);
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 30]);
        $other = Parallel::create(['name' => 'B', 'course_id' => $course->id, 'max_students' => 30]);

        // Se crean fuera de orden alfabético y con ids altos a propósito.
        foreach ([
            ['Berrios', 'Zarai', 'Mishel'],
            ['Alanes', 'Cruz', 'Raul'],
            ['Aliaga', 'Salinas', 'Stiven'],
        ] as [$last, $second, $first]) {
            Student::create([
                'name' => "$last $second $first", 'last_name' => $last, 'second_last_name' => $second, 'first_name' => $first,
                'course' => $course->name, 'course_id' => $course->id, 'parallel_id' => $parallel->id, 'status' => 'activo',
            ]);
        }
        Student::create([
            'name' => 'Zeta Otro Paralelo', 'last_name' => 'Zeta', 'second_last_name' => 'Otro', 'first_name' => 'Paralelo',
            'course' => $course->name, 'course_id' => $course->id, 'parallel_id' => $other->id, 'status' => 'activo',
        ]);

        return [$parallel, Subject::where('code', 'QUE')->firstOrFail()];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    /** Devuelve [número, apellido] de cada fila de la tabla, en el orden en que salen. */
    private function rows(string $html, string $firstCellTag = 'td'): array
    {
        preg_match_all('/<tr[^>]*data-student-id="(\d+)"[^>]*>\s*<td[^>]*>\s*(\d+)\s*<\/td>.*?<span class="font-weight-bold">\s*([^<]+?)\s*<\/span>/s', $html, $m, PREG_SET_ORDER);

        return array_map(fn ($r) => [(int) $r[2], $r[3]], $m);
    }

    public function test_grades_table_numbers_the_alphabetical_list_of_one_parallel(): void
    {
        [$parallel, $subject] = $this->seedList();

        $response = $this->actingAs($this->admin())->get("/grades?subject_id={$subject->id}&parallel_id={$parallel->id}&quarter=1")->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('>Nro</th>', $html);
        $this->assertStringNotContainsString('>ID</th>', $html);

        $rows = $this->rows($html);
        $this->assertSame([1, 2, 3], array_column($rows, 0), 'la numeración debe ser 1, 2, 3 del paralelo elegido');
        $this->assertSame(['ALANES', 'ALIAGA', 'BERRIOS'], array_map(fn ($r) => strtoupper(explode(' ', $r[1])[0]), $rows));
        // El id de la base de datos no se muestra como número.
        foreach (Student::pluck('id') as $id) {
            if ($id > 3) {
                $this->assertDoesNotMatchRegularExpression('/<td class="text-center align-middle">\s*' . $id . '\s*<\/td>/', $html);
            }
        }
    }

    public function test_attendance_table_uses_the_same_numbering(): void
    {
        [$parallel, $subject] = $this->seedList();

        $html = $this->actingAs($this->admin())->get("/attendance?subject_id={$subject->id}&parallel_id={$parallel->id}")->assertOk()->getContent();

        $this->assertStringContainsString('>Nro</th>', $html);
        $this->assertSame([1, 2, 3], array_column($this->rows($html), 0));
    }

    public function test_the_pdf_exports_still_render_with_the_new_column(): void
    {
        [$parallel, $subject] = $this->seedList();
        $admin = $this->admin();

        $this->actingAs($admin)->get("/grades/export-pdf?subject_id={$subject->id}&parallel_id={$parallel->id}&quarter=1")->assertOk();
        $this->actingAs($admin)->get("/attendance/export-pdf?subject_id={$subject->id}&parallel_id={$parallel->id}")->assertOk();
    }
}
