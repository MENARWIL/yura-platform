<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Parallel;
use App\Models\Student;
use App\Models\StudentFamilyMember;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SchoolPilotSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bolivian surnames for families
        $bolivianSurnames = [
            'Quispe', 'Choque', 'Mamani', 'Condori', 'Flores',
            'Vargas', 'Rojas', 'Morales', 'Paco', 'Alanoca',
            'Callisaya', 'Cusionca', 'Apaza', 'Llanos', 'Gutierrez',
            'Ramos', 'Tito', 'Huanca', 'Catacora', 'Machaca'
        ];

        $studentFirstNames = [
            'Juan', 'María', 'Carlos', 'Ana', 'Luis',
            'Rosa', 'Pedro', 'Laura', 'Miguel', 'Carmen',
            'Diego', 'Patricia', 'Andrés', 'Elena', 'Roberto',
            'Claudia', 'Fernando', 'Silvia', 'Raúl', 'Isabel'
        ];

        $credentials = [];

        // 1. Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@yura.com'],
            [
                'name' => 'Administrador Principal',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'telefono' => '70101010',
                'status' => 'activo',
            ]
        );
        $credentials[] = "🔑 ADMINISTRADOR: admin@yura.com (password123)";

        // 2. Academic User
        $academic = User::updateOrCreate(
            ['email' => 'gestion.yura@school.bo'],
            [
                'name' => 'Lic. Gestión Académica',
                'password' => Hash::make('password123'),
                'role' => 'academic',
                'telefono' => '70101112',
                'status' => 'activo',
            ]
        );
        $credentials[] = "📋 Gestor Académico: gestion.yura@school.bo (password123)";

        // 3. Teachers
        $teacherData = [
            ['email' => 'prof.vargas@school.bo', 'name' => 'Prof. Carlos Vargas'],
            ['email' => 'prof.flores@school.bo', 'name' => 'Prof. María Flores']
        ];
        $teachers = [];

        foreach ($teacherData as $data) {
            $teacher = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make('password123'),
                    'role' => 'profesor',
                    'telefono' => '70' . rand(100000, 999999),
                    'status' => 'activo',
                ]
            );
            $teachers[] = $teacher;
            $credentials[] = "👨‍🏫 {$data['name']}: {$data['email']} (password123)";
        }

        // 4. Course: "Sexto de Primaria"
        $course = Course::updateOrCreate(
            ['name' => 'Sexto de Primaria'],
            []
        );

        // 5. Create Parallels (A and B)
        $parallels = [];
        foreach (['A', 'B'] as $parallelName) {
            $parallel = Parallel::updateOrCreate(
                ['course_id' => $course->id, 'name' => $parallelName],
                ['max_students' => 20]
            );
            $parallels[] = $parallel;
        }

        // 6. Create Tutors with Bolivian surnames (20 total)
        $tutors = [];
        foreach ($bolivianSurnames as $index => $surname) {
            $firstName = $studentFirstNames[$index] ?? 'Tutor';
            $tutor = User::updateOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $firstName)) . '.' . strtolower($surname) . '@school.bo'],
                [
                    'name' => "$firstName $surname",
                    'password' => Hash::make('password123'),
                    'role' => 'padre',
                    'telefono' => '70' . rand(100000, 999999),
                    'status' => 'activo',
                ]
            );
            $tutors[] = $tutor;
        }

        // 7. Create Students (10 per parallel, 20 total)
        $studentIndex = 0;
        foreach ($parallels as $parallelIndex => $parallel) {
            for ($i = 1; $i <= 10; $i++) {
                $firstName = $studentFirstNames[$studentIndex % count($studentFirstNames)];
                $surname = $bolivianSurnames[$studentIndex % count($bolivianSurnames)];
                $student = Student::updateOrCreate(
                    ['name' => "$firstName $surname - $i"],
                    [
                        'course' => $parallel->course->name,
                        'course_id' => $parallel->course_id,
                        'parallel_id' => $parallel->id,
                        'level' => 'básico',
                        'gender' => rand(0, 1) ? 'Masculino' : 'Femenino',
                        'age' => rand(11, 12),
                        'registration_date' => now(),
                        'status' => 'activo',
                        'attendance' => 90,
                        // Assign a teacher from the teachers list
                        'teacher_user_id' => $teachers[$parallelIndex % count($teachers)]->id,
                    ]
                );

                // Assign a tutor from the tutors list with relationship "padre"
                $assignedTutor = $tutors[$studentIndex % count($tutors)];
                $student->familiares()->syncWithoutDetaching([
                    $assignedTutor->id => [
                        'relation_type' => 'padre',
                        'is_primary' => true,
                        'can_view' => true,
                        'can_edit' => false,
                        'can_receive_reports' => true,
                        'active' => true,
                    ],
                ]);

                $studentIndex++;
            }
        }

        // 8. Console output with credentials
        $this->command->info("\n" . str_repeat("=", 70));
        $this->command->info("🏫 ESCUELA PILOTO YURA - COCHABAMBA, BOLIVIA");
        $this->command->info("📍 Nivel: Sexto de Primaria");
        $this->command->info(str_repeat("=", 70));

        $this->command->info("\n📚 ESTRUCTURA ACADÉMICA:");
        $this->command->info("   • Sexto de Primaria - Paralelo A (10 estudiantes)");
        $this->command->info("   • Sexto de Primaria - Paralelo B (10 estudiantes)");

        $this->command->info("\n👥 CUENTAS DE ACCESO:");
        $this->command->info("   " . implode("\n   ", $credentials));

        $this->command->info("\n👨‍🏫 ASIGNACIONES DE DOCENTES:");
        $this->command->info("   • Prof. Carlos Vargas → Sexto de Primaria - Paralelo A");
        $this->command->info("   • Prof. María Flores → Sexto de Primaria - Paralelo B");

        $this->command->info("\n👨‍👩‍👧 TUTORES Y FAMILIAS: 20 tutores registrados con apellidos bolivianos");
        $this->command->info("   Email: {nombre}.{apellido}@school.bo | Contraseña: password123");
        $this->command->info("   Cada estudiante vinculado con tutor principal (is_primary=true)");

        $this->command->info("\n📌 PRÓXIMOS PASOS:");
        $this->command->info("   1. Acceder a http://yura.local/login");
        $this->command->info("   2. Usar credenciales de arriba");
        $this->command->info("   3. Gestor Académico puede registrar estudiantes, tutores y crear paralelos");

        $this->command->info("\n" . str_repeat("=", 70) . "\n");
    }
}
