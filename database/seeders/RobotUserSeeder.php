<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RobotUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $robot = User::firstOrCreate(
            ['email' => 'robot@yura.com'],
            [
                'name' => 'Robot YURA',
                'password' => Hash::make('robot-password-123'),
                'role' => 'robot',
                'estado' => 'activo',
            ]
        );

        $token = $robot->createToken('robot-api-token')->plainTextToken;

        $this->command->info("Robot API token: {$token}");
    }
}
