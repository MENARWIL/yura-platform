<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La migración 2026_05_26_000001_add_estado_aprobacion_to_estudiantes agregó la
 * columna "estado_aprobacion" a la tabla "estudiantes" (nombre anterior de
 * "students") y está marcada como "Ran" en el historial de migraciones, pero la
 * columna ya no existe en la base de datos real y ningún archivo del código la
 * usa (confirmado por auditoría: grep sin resultados en app/, resources/ y
 * routes/). Ninguna migración anterior documenta explícitamente su eliminación.
 *
 * Esta migración deja constancia formal de ese estado (no-op si la columna ya
 * no existe, para no romper si se ejecuta migrate:fresh) en vez de dejar el
 * historial de migraciones desincronizado de la realidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'estado_aprobacion')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('estado_aprobacion');
            });
        }
    }

    public function down(): void
    {
        // No se restaura: la columna no tiene ningún consumidor en el código
        // actual. Si se necesita en el futuro, debe agregarse con una migración
        // nueva que también implemente su uso real.
    }
};
