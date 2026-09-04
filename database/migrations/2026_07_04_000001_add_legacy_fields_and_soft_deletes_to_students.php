<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'usuario_id')) {
                $table->foreignId('usuario_id')->nullable()->after('parallel_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('students', 'profesor_id')) {
                $table->foreignId('profesor_id')->nullable()->after('usuario_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('students', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('profesor_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('students', 'edad')) {
                $table->integer('edad')->nullable()->after('name');
            }

            if (! Schema::hasColumn('students', 'genero')) {
                $table->enum('genero', ['Masculino', 'Femenino'])->nullable()->after('edad');
            }

            if (! Schema::hasColumn('students', 'nivel')) {
                $table->enum('nivel', ['básico', 'intermedio', 'avanzado'])->nullable()->after('genero');
            }

            if (! Schema::hasColumn('students', 'fecha_registro')) {
                $table->date('fecha_registro')->nullable()->after('nivel');
            }

            if (! Schema::hasColumn('students', 'estado')) {
                $table->string('estado')->default('activo')->after('profesor_id');
            }

            if (! Schema::hasColumn('students', 'puntaje')) {
                $table->integer('puntaje')->default(0)->after('estado');
            }

            if (! Schema::hasColumn('students', 'nota_escritura')) {
                $table->integer('nota_escritura')->default(0)->after('puntaje');
            }

            if (! Schema::hasColumn('students', 'nota_examen')) {
                $table->integer('nota_examen')->default(0)->after('nota_escritura');
            }

            if (! Schema::hasColumn('students', 'asistencia')) {
                $table->integer('asistencia')->default(100)->after('nota_examen');
            }

            if (! Schema::hasColumn('students', 'foto_path')) {
                $table->string('foto_path')->nullable()->after('estado');
            }

            if (! Schema::hasColumn('students', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (Schema::hasTable('student_family_members')) {
            Schema::table('student_family_members', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_family_members')) {
            Schema::table('student_family_members', function (Blueprint $table) {
                $table->dropForeign(['student_id']);
                $table->foreign('student_id')->references('id')->on('estudiantes')->cascadeOnDelete();
            });
        }

        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach ([
                'foto_path',
                'asistencia',
                'nota_examen',
                'nota_escritura',
                'puntaje',
                'estado',
                'fecha_registro',
                'nivel',
                'genero',
                'edad',
                'user_id',
                'profesor_id',
                'usuario_id',
            ] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
