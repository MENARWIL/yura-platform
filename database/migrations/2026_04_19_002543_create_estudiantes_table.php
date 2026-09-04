<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade'); // Padre
            $table->foreignId('profesor_id')->nullable()->constrained('users')->onDelete('set null'); // Profesor
            $table->string('nombre');
            $table->integer('edad');
            $table->enum('genero', ['Masculino', 'Femenino']);
            $table->enum('nivel', ['básico', 'intermedio', 'avanzado']);
            $table->date('fecha_registro');
            $table->string('estado')->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
