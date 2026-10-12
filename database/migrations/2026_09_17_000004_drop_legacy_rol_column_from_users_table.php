<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolida users.role/users.rol en una sola columna. La auditoría de base de
 * datos confirmó que ambas columnas coexistían con el mismo valor por defecto
 * ('padre') y que, para los 5 usuarios con un rol distinto de 'padre' (admin,
 * academic, 2 profesores, robot), "rol" había quedado desincronizada respecto
 * a "role" (bug real en el mutador de compatibilidad del modelo User, ya
 * corregido en el mismo cambio). "role" es la columna correcta y completa;
 * "rol" se elimina. El modelo User mantiene un alias ->rol de solo lectura
 * por compatibilidad, sin columna real detrás.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'rol')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('rol');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'rol')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('rol')->default('padre')->after('role');
            });
        }
    }
};
