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
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->integer('nota_escritura')->default(0)->after('puntaje');
            $table->integer('nota_examen')->default(0)->after('nota_escritura');
            $table->integer('asistencia')->default(100)->after('nota_examen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['nota_escritura', 'nota_examen', 'asistencia']);
        });
    }
};
