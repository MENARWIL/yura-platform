<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contrato v1.3: el robot deja de ser un usuario y pasa a ser un dispositivo registrado, con una llave
 * propia (token de Sanctum con permisos limitados) que el administrador genera, renueva y revoca desde la web.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('robot_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->index(); // active | revoked
            $table->string('key_hint', 8)->nullable();                 // últimos caracteres de la llave, para reconocerla
            $table->timestamp('key_created_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        // Qué robot reclamó cada intento: solo ese robot puede enviar sus resultados.
        Schema::table('robot_activity_attempts', function (Blueprint $table) {
            $table->foreignId('robot_device_id')->nullable()->after('robot_activity_id')
                ->constrained('robot_devices')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('robot_activity_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('robot_device_id');
        });
        Schema::dropIfExists('robot_devices');
    }
};
