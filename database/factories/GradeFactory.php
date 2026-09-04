<?php

namespace Database\Factories;

use App\Models\Grade;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition()
    {
        $studentId = DB::table('students')->inRandomOrder()->value('id') ?: 1;
        $subjectId = DB::table('courses')->inRandomOrder()->value('id') ?: 1;

        return [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'score' => $this->faker->randomFloat(2, 0, 100),
            'type' => $this->faker->randomElement(['actividad', 'examen', 'trabajo_practico']),
            'observations' => $this->faker->optional()->sentence(),
        ];
    }
}
