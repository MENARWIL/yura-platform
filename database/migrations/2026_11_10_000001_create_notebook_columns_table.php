<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuántas casillas SER, SABER y HACER usa el profesor en el cuaderno pedagógico,
 * por gestión, materia, paralelo y trimestre. Sin fila, se muestran 2 casillas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notebook_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('parallel_id')->constrained('parallels')->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');
            $table->string('dimension', 10); // ser | saber | hacer
            $table->unsignedTinyInteger('count');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['academic_year_id', 'subject_id', 'parallel_id', 'quarter', 'dimension'], 'notebook_columns_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notebook_columns');
    }
};
