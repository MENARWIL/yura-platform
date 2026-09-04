<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Course as Subject;

class GradesAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = Student::all();
        $subjects = Subject::all();

        if ($students->isEmpty() || $subjects->isEmpty()) {
            $this->command->info('No hay estudiantes o materias suficientes para sembrar grades/attendance.');
            return;
        }

        foreach ($students as $student) {
            // create 2-4 random grades per student
            $subjects->random(min(3, $subjects->count()))->each(function ($subject) use ($student) {
                Grade::factory()->create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                ]);
            });

            // create attendance for last 7 days for random subjects
            $subjects->random(min(2, $subjects->count()))->each(function ($subject) use ($student) {
                for ($d = 0; $d < 7; $d++) {
                    Attendance::factory()->create([
                        'student_id' => $student->id,
                        'subject_id' => $subject->id,
                        'date' => now()->subDays($d)->toDateString(),
                    ]);
                }
            });
        }
    }
}
