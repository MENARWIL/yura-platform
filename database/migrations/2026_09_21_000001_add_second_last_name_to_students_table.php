<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el apellido materno al estudiante. Las columnas first_name (nombre)
 * y last_name (apellido paterno) ya existían desde
 * 2026_07_20_000001_add_academic_and_robot_fields pero no se usaban en
 * ningún formulario; ahora los tres campos (first_name, last_name,
 * second_last_name) se capturan por separado en el alta/edición de
 * estudiante y se combinan en la columna name (Apellido Paterno + Apellido
 * Materno + Nombre) para no romper ninguna vista, exportación o reporte
 * existente que ya lee $student->name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'second_last_name')) {
                $table->string('second_last_name')->nullable()->after('last_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'second_last_name')) {
                $table->dropColumn('second_last_name');
            }
        });
    }
};
