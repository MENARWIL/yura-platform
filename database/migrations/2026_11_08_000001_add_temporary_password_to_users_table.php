<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contraseñas temporales enviadas por WhatsApp: la Dirección, el administrador principal o la
 * Secretaría generan una clave que vence y obliga al usuario a cambiarla al entrar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('password_must_change')->default(false)->after('password');
            $table->timestamp('temporary_password_expires_at')->nullable()->after('password_must_change');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['password_must_change', 'temporary_password_expires_at']);
        });
    }
};
