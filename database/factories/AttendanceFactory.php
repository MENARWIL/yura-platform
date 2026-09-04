<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        $studentId = DB::table('students')->inRandomOrder()->value('id') ?: 1;
        $subjectId = DB::table('courses')->inRandomOrder()->value('id') ?: 1;

        return [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'date' => Carbon::now()->subDays(rand(0, 10))->toDateString(),
            'status' => $this->faker->randomElement(['presente', 'falta', 'licencia', 'retraso']),
        ];
    }
}
