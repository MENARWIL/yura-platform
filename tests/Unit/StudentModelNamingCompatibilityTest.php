<?php

namespace Tests\Unit;

use App\Models\Student;
use PHPUnit\Framework\TestCase;

class StudentModelNamingCompatibilityTest extends TestCase
{
    public function test_student_model_supports_english_aliases_for_common_attributes(): void
    {
        $student = new Student();

        $student->full_name = 'Ana Pérez';
        $student->parent_user_id = 42;
        $student->teacher_user_id = 7;
        $student->registration_date = '2026-01-05';
        $student->writing_score = 80;
        $student->exam_score = 90;

        $this->assertSame('Ana Pérez', $student->full_name);
        $this->assertSame('Ana Pérez', $student->nombre);
        $this->assertSame(42, $student->parent_user_id);
        $this->assertSame(42, $student->usuario_id);
        $this->assertSame(7, $student->teacher_user_id);
        $this->assertSame(7, $student->profesor_id);
        $this->assertSame('2026-01-05', $student->registration_date);
        $this->assertSame('2026-01-05', $student->fecha_registro);
        $this->assertSame(80, $student->writing_score);
        $this->assertSame(80, $student->nota_escritura);
        $this->assertSame(90, $student->exam_score);
        $this->assertSame(90, $student->nota_examen);
    }
}
