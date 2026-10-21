<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Depuración de la base (auditoría de bases de datos, 18-10-2026).
 *
 * 1) Retira las tablas del módulo de trámites, eliminado del sistema: ninguna parte del código las usa.
 *    Se borran de las hijas a la madre. down() no las recrea: el respaldo previo es el camino de vuelta.
 * 2) Agrega dos índices compuestos para las consultas más frecuentes de la gestión escolar.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['procedure_attachments', 'procedure_histories', 'procedures', 'procedure_types'] as $table) {
            Schema::dropIfExists($table);
        }

        if (! Schema::hasIndex('grades', 'grades_student_subject_quarter_index')) {
            Schema::table('grades', function (Blueprint $table) {
                // Búsqueda de las notas de un estudiante por materia y trimestre (cuaderno y panel de la familia).
                $table->index(['student_id', 'subject_id', 'quarter'], 'grades_student_subject_quarter_index');
            });
        }

        if (! Schema::hasIndex('enrollments', 'enrollments_year_parallel_index')) {
            Schema::table('enrollments', function (Blueprint $table) {
                // Listas de un paralelo en una gestión.
                $table->index(['academic_year_id', 'parallel_id'], 'enrollments_year_parallel_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('enrollments', 'enrollments_year_parallel_index')) {
            Schema::table('enrollments', fn (Blueprint $table) => $table->dropIndex('enrollments_year_parallel_index'));
        }
        if (Schema::hasIndex('grades', 'grades_student_subject_quarter_index')) {
            Schema::table('grades', fn (Blueprint $table) => $table->dropIndex('grades_student_subject_quarter_index'));
        }
        // Las tablas de trámites no se recrean (módulo eliminado).
    }
};
