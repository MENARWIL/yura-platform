<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bajas de estudiantes (transferencia a otra unidad educativa o retiro). El estudiante no se
 * borra: pasa a estado «inactivo» y aquí queda el motivo y una copia de sus datos de ese momento.
 * Si se reincorpora, la baja queda cerrada (reverted_at) como historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('type', 20); // transferencia | retiro
            $table->string('destination')->nullable(); // unidad educativa de destino (transferencia)
            $table->text('reason')->nullable();
            $table->date('withdrawn_on');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('snapshot')->nullable(); // respaldo: identidad, curso, responsables, notas y asistencia
            $table->timestamp('reverted_at')->nullable();
            $table->foreignId('reverted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_id', 'reverted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_withdrawals');
    }
};
