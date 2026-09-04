<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'rol') && ! Schema::hasColumn('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('rol', 'role');
                });
            }

            if (Schema::hasColumn('users', 'telefono') && ! Schema::hasColumn('users', 'phone')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('telefono', 'phone');
                });
            }

            if (Schema::hasColumn('users', 'estado') && ! Schema::hasColumn('users', 'status')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('estado', 'status');
                });
            }
        }

        if (Schema::hasTable('students')) {
            if (Schema::hasColumn('students', 'usuario_id') && ! Schema::hasColumn('students', 'parent_user_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('usuario_id', 'parent_user_id');
                });
            }

            if (Schema::hasColumn('students', 'profesor_id') && ! Schema::hasColumn('students', 'teacher_user_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('profesor_id', 'teacher_user_id');
                });
            }

            if (Schema::hasColumn('students', 'user_id') && ! Schema::hasColumn('students', 'student_user_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('user_id', 'student_user_id');
                });
            }

            if (Schema::hasColumn('students', 'edad') && ! Schema::hasColumn('students', 'age')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('edad', 'age');
                });
            }

            if (Schema::hasColumn('students', 'genero') && ! Schema::hasColumn('students', 'gender')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('genero', 'gender');
                });
            }

            if (Schema::hasColumn('students', 'nivel') && ! Schema::hasColumn('students', 'level')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('nivel', 'level');
                });
            }

            if (Schema::hasColumn('students', 'fecha_registro') && ! Schema::hasColumn('students', 'registration_date')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('fecha_registro', 'registration_date');
                });
            }

            if (Schema::hasColumn('students', 'estado') && ! Schema::hasColumn('students', 'status')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('estado', 'status');
                });
            }

            if (Schema::hasColumn('students', 'puntaje') && ! Schema::hasColumn('students', 'score')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('puntaje', 'score');
                });
            }

            if (Schema::hasColumn('students', 'nota_escritura') && ! Schema::hasColumn('students', 'writing_score')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('nota_escritura', 'writing_score');
                });
            }

            if (Schema::hasColumn('students', 'nota_examen') && ! Schema::hasColumn('students', 'exam_score')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('nota_examen', 'exam_score');
                });
            }

            if (Schema::hasColumn('students', 'asistencia') && ! Schema::hasColumn('students', 'attendance')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('asistencia', 'attendance');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students')) {
            if (Schema::hasColumn('students', 'parent_user_id') && ! Schema::hasColumn('students', 'usuario_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('parent_user_id', 'usuario_id');
                });
            }

            if (Schema::hasColumn('students', 'teacher_user_id') && ! Schema::hasColumn('students', 'profesor_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('teacher_user_id', 'profesor_id');
                });
            }

            if (Schema::hasColumn('students', 'student_user_id') && ! Schema::hasColumn('students', 'user_id')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('student_user_id', 'user_id');
                });
            }

            if (Schema::hasColumn('students', 'age') && ! Schema::hasColumn('students', 'edad')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('age', 'edad');
                });
            }

            if (Schema::hasColumn('students', 'gender') && ! Schema::hasColumn('students', 'genero')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('gender', 'genero');
                });
            }

            if (Schema::hasColumn('students', 'level') && ! Schema::hasColumn('students', 'nivel')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('level', 'nivel');
                });
            }

            if (Schema::hasColumn('students', 'registration_date') && ! Schema::hasColumn('students', 'fecha_registro')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('registration_date', 'fecha_registro');
                });
            }

            if (Schema::hasColumn('students', 'status') && ! Schema::hasColumn('students', 'estado')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('status', 'estado');
                });
            }

            if (Schema::hasColumn('students', 'score') && ! Schema::hasColumn('students', 'puntaje')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('score', 'puntaje');
                });
            }

            if (Schema::hasColumn('students', 'writing_score') && ! Schema::hasColumn('students', 'nota_escritura')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('writing_score', 'nota_escritura');
                });
            }

            if (Schema::hasColumn('students', 'exam_score') && ! Schema::hasColumn('students', 'nota_examen')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('exam_score', 'nota_examen');
                });
            }

            if (Schema::hasColumn('students', 'attendance') && ! Schema::hasColumn('students', 'asistencia')) {
                Schema::table('students', function (Blueprint $table) {
                    $table->renameColumn('attendance', 'asistencia');
                });
            }
        }

        if (Schema::hasTable('users')) {
            if (Schema::hasColumn('users', 'role') && ! Schema::hasColumn('users', 'rol')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('role', 'rol');
                });
            }

            if (Schema::hasColumn('users', 'phone') && ! Schema::hasColumn('users', 'telefono')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('phone', 'telefono');
                });
            }

            if (Schema::hasColumn('users', 'status') && ! Schema::hasColumn('users', 'estado')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->renameColumn('status', 'estado');
                });
            }
        }
    }
};
