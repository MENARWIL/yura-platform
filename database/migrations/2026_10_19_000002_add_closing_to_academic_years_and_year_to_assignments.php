<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cierre de gestión (closed_at/closed_by) y asignaciones docentes por gestión.
 * Las asignaciones que ya existen pasan a la gestión actual (2026).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        if (! Schema::hasColumn('teaching_assignments', 'academic_year_id')) {
            $yearId = DB::table('academic_years')->where('is_current', true)->value('id');

            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            });
            DB::table('teaching_assignments')->update(['academic_year_id' => $yearId]);

            // La misma materia y paralelo se repite cada gestión: la unicidad pasa a incluir el año.
            Schema::table('teaching_assignments', function (Blueprint $table) {
                $table->dropUnique(['subject_id', 'parallel_id']);
                $table->unique(['subject_id', 'parallel_id', 'academic_year_id'], 'teaching_assignments_subject_parallel_year_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('teaching_assignments', function (Blueprint $table) {
            $table->dropUnique('teaching_assignments_subject_parallel_year_unique');
            $table->dropConstrainedForeignId('academic_year_id');
            $table->unique(['subject_id', 'parallel_id']);
        });

        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by');
            $table->dropColumn('closed_at');
        });
    }
};
