<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\GradesAttendanceSeeder;
use Database\Seeders\RobotUserSeeder;
use Database\Seeders\SchoolPilotSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run the school pilot seeder for comprehensive test data
        $this->call(SchoolPilotSeeder::class);

        // seed grades and attendance for existing students/subjects
        $this->call(GradesAttendanceSeeder::class);

        // seed robot API user and token
        $this->call(RobotUserSeeder::class);
    }
}
