<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@yura.com'],
            [
                'name' => 'Admin YURA',
                'password' => Hash::make('password123'),
                'rol' => 'admin',
                'telefono' => '70010203',
                'estado' => 'activo',
            ]
        );

        $profesor = User::updateOrCreate(
            ['email' => 'profesor@yura.com'],
            [
                'name' => 'Prof. Wilson Mendieta',
                'password' => Hash::make('password123'),
                'rol' => 'profesor',
                'telefono' => '70040506',
                'estado' => 'activo',
            ]
        );

        $padre = User::updateOrCreate(
            ['email' => 'padre@yura.com'],
            [
                'name' => 'Padre de Familia',
                'password' => Hash::make('password123'),
                'rol' => 'padre',
                'telefono' => '70070809',
                'estado' => 'activo',
            ]
        );

        $madre = User::updateOrCreate(
            ['email' => 'madre@yura.com'],
            [
                'name' => 'Madre de Familia',
                'password' => Hash::make('password123'),
                'rol' => 'madre',
                'telefono' => '70081011',
                'estado' => 'activo',
            ]
        );

        $tutor = User::updateOrCreate(
            ['email' => 'tutor@yura.com'],
            [
                'name' => 'Tutor de Familia',
                'password' => Hash::make('password123'),
                'rol' => 'tutor',
                'telefono' => '70091213',
                'estado' => 'activo',
            ]
        );

        $students = [
            [
                'nombre' => 'Josesito Perez',
                'usuario_id' => $padre->id,
                'profesor_id' => $profesor->id,
                'edad' => 10,
                'genero' => 'Masculino',
                'nivel' => 'básico',
                'puntaje' => 85,
            ],
            [
                'nombre' => 'Maria Garcia',
                'usuario_id' => $padre->id,
                'profesor_id' => $profesor->id,
                'edad' => 12,
                'genero' => 'Femenino',
                'nivel' => 'intermedio',
                'puntaje' => 92,
            ],
            [
                'nombre' => 'Killa Mendieta',
                'usuario_id' => $padre->id,
                'profesor_id' => null,
                'edad' => 8,
                'genero' => 'Femenino',
                'nivel' => 'básico',
                'puntaje' => 45,
            ],
        ];

        foreach ($students as $studentData) {
            $student = Student::updateOrCreate(
                ['nombre' => $studentData['nombre'], 'usuario_id' => $studentData['usuario_id']],
                [
                    'profesor_id' => $studentData['profesor_id'],
                    'edad' => $studentData['edad'],
                    'genero' => $studentData['genero'],
                    'nivel' => $studentData['nivel'],
                    'fecha_registro' => now(),
                    'estado' => 'activo',
                    'puntaje' => $studentData['puntaje'],
                ]
            );

            $account = User::updateOrCreate(
                ['email' => Str::slug($studentData['nombre']) . '@yura.local'],
                [
                    'name' => $studentData['nombre'],
                    'password' => Hash::make('password123'),
                    'rol' => 'estudiante',
                    'telefono' => null,
                    'estado' => 'activo',
                ]
            );

            $student->user_id = $account->id;
            $student->save();

            StudentFamilyMember::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'user_id' => $madre->id,
                ],
                [
                    'relation_type' => 'madre',
                    'is_primary' => false,
                    'can_view' => true,
                    'can_edit' => false,
                    'can_receive_reports' => true,
                    'active' => true,
                ]
            );

            StudentFamilyMember::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'user_id' => $tutor->id,
                ],
                [
                    'relation_type' => 'tutor',
                    'is_primary' => false,
                    'can_view' => true,
                    'can_edit' => false,
                    'can_receive_reports' => true,
                    'active' => true,
                ]
            );
        }
    }
}
