<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Contrato v1.2 (en retiro). El robot ya no necesita un usuario: se registra en la web («Robot YURA») o con
 * `php artisan robot:llave` y entra con su propia llave. Esta semilla solo deja la cuenta antigua, sin contraseña
 * conocida y sin token, para las bases que todavía la usan; no puede iniciar sesión.
 */
class RobotUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'robot@yura.com'],
            [
                'name' => 'Robot YURA',
                'password' => Hash::make(Str::random(48)),
                'role' => 'robot',
                'estado' => 'activo',
            ]
        );
    }
}
