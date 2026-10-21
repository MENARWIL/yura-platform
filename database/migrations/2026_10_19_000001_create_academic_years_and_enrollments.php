<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gestión escolar (año académico) y matrículas.
 * Todo lo que existe hoy pasa a la gestión 2026, que queda como la actual.
 */
return new class extends Migration
{
    private array $yearTables = ['grades', 'attendance', 'robot_activities'];

    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('name', 60);
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        $now = now();
        $yearId = DB::table('academic_years')->insertGetId([
            'year' => 2026, 'name' => 'Gestión 2026', 'is_current' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach ($this->yearTables as $name) {
            if (! Schema::hasTable($name) || Schema::hasColumn($name, 'academic_year_id')) {
                continue;
            }
            Schema::table($name, function (Blueprint $table) {
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            });
            DB::table($name)->update(['academic_year_id' => $yearId]);
        }

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->foreignId('parallel_id')->nullable()->constrained('parallels')->nullOnDelete();
            $table->string('status', 20)->default('activo');
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });

        // Una matrícula 2026 por cada estudiante existente (incluidos los eliminados, para no perder su historial).
        DB::table('students')->orderBy('id')->chunkById(200, function ($students) use ($yearId, $now) {
            DB::table('enrollments')->insert($students->map(fn ($s) => [
                'student_id' => $s->id,
                'academic_year_id' => $yearId,
                'course_id' => $s->course_id,
                'parallel_id' => $s->parallel_id,
                'status' => 'activo',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');

        foreach ($this->yearTables as $name) {
            if (Schema::hasTable($name) && Schema::hasColumn($name, 'academic_year_id')) {
                Schema::table($name, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('academic_year_id');
                });
            }
        }

        Schema::dropIfExists('academic_years');
    }
};
