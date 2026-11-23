<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Nombres de estudiantes y familiares: se guardan siempre en MAYÚSCULAS, por cualquier camino. */
class UppercaseNamesTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $parts): Student
    {
        $course = Course::create(['name' => 'Sexto de Primaria']);
        $parallel = Parallel::create(['name' => 'A', 'course_id' => $course->id, 'max_students' => 30]);

        return Student::create($parts + [
            'course' => $course->name, 'course_id' => $course->id,
            'parallel_id' => $parallel->id, 'status' => 'activo',
        ]);
    }

    public function test_student_name_parts_are_saved_in_uppercase_with_accents(): void
    {
        $student = $this->student(['last_name' => 'aduviri', 'second_last_name' => 'ñuflo', 'first_name' => '  josé   fernando ']);

        $this->assertSame('ADUVIRI', $student->fresh()->last_name);
        $this->assertSame('ÑUFLO', $student->fresh()->second_last_name);
        $this->assertSame('JOSÉ FERNANDO', $student->fresh()->first_name);
        $this->assertSame('ADUVIRI ÑUFLO JOSÉ FERNANDO', $student->fresh()->name);
    }

    public function test_student_edited_in_lowercase_stays_uppercase(): void
    {
        $student = $this->student(['last_name' => 'TORRES', 'first_name' => 'LEO']);
        $student->update(['first_name' => 'leonardo']);

        $this->assertSame('LEONARDO', $student->fresh()->first_name);
        $this->assertNull($student->fresh()->second_last_name);
    }

    public function test_family_member_name_is_saved_in_uppercase(): void
    {
        foreach (['padre', 'madre', 'tutor'] as $role) {
            $user = User::factory()->create([
                'rol' => $role, 'estado' => 'activo', 'name' => 'jorge guarachi franklin',
                'first_name' => 'jorge', 'last_name' => 'guarachi', 'second_last_name' => 'franklin',
            ]);

            $this->assertSame('JORGE GUARACHI FRANKLIN', $user->fresh()->name, $role);
            $this->assertSame('JORGE', $user->fresh()->first_name, $role);
            $this->assertSame('GUARACHI', $user->fresh()->last_name, $role);
            $this->assertSame('FRANKLIN', $user->fresh()->second_last_name, $role);
        }
    }

    public function test_names_saved_before_the_rule_are_shown_in_uppercase_without_touching_the_table(): void
    {
        $student = $this->student(['last_name' => 'ADUVIRI', 'first_name' => 'FERNANDO']);
        $parent = User::factory()->create(['rol' => 'tutor', 'estado' => 'activo', 'name' => 'X']);
        // Como quedaron en la base antes de esta regla.
        \DB::table('students')->where('id', $student->id)->update(['last_name' => 'Aduviri', 'second_last_name' => 'cornelio', 'first_name' => 'Fernando']);
        \DB::table('users')->where('id', $parent->id)->update(['name' => 'jorge guarachi franklin', 'first_name' => 'jorge']);

        $this->assertSame('ADUVIRI CORNELIO FERNANDO', $student->fresh()->name);
        $this->assertSame('CORNELIO', $student->fresh()->second_last_name);
        $this->assertSame('ADUVIRI CORNELIO FERNANDO', $student->fresh()->toArray()['name']); // API y app
        $this->assertSame('JORGE GUARACHI FRANKLIN', $parent->fresh()->name);
        $this->assertSame('JORGE', $parent->fresh()->first_name);
        $this->assertSame('cornelio', \DB::table('students')->where('id', $student->id)->value('second_last_name'));
    }

    public function test_staff_names_and_family_emails_are_not_changed(): void
    {
        $teacher = User::factory()->create(['rol' => 'profesor', 'estado' => 'activo', 'name' => 'Carlos Vargas', 'first_name' => 'Carlos']);
        $parent = User::factory()->create(['rol' => 'padre', 'estado' => 'activo', 'name' => 'ana', 'email' => 'ana.flores@yura.test']);

        $this->assertSame('Carlos Vargas', $teacher->fresh()->name);
        $this->assertSame('Carlos', $teacher->fresh()->first_name);
        $this->assertSame('ana.flores@yura.test', $parent->fresh()->email);
    }
}
