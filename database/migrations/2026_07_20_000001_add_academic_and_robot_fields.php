<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'main_course_id')) {
                    $position = Schema::hasColumn('users', 'estado') ? 'estado' : 'status';
                    $table->foreignId('main_course_id')->nullable()->after($position)->constrained('courses')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::hasColumn('grades', 'status')) {
                    $table->string('status')->default('completed')->after('observations');
                }

                if (!Schema::hasColumn('grades', 'activity_category')) {
                    $table->string('activity_category')->nullable()->after('status');
                }

                if (!Schema::hasColumn('grades', 'is_robot_activity')) {
                    $table->boolean('is_robot_activity')->default(false)->after('activity_category');
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (!Schema::hasColumn('students', 'last_name')) {
                    $table->string('last_name')->nullable()->after('name');
                }

                if (!Schema::hasColumn('students', 'first_name')) {
                    $table->string('first_name')->nullable()->after('last_name');
                }

                if (!Schema::hasColumn('students', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('registration_date');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'main_course_id')) {
                    $table->dropForeign(['main_course_id']);
                    $table->dropColumn('main_course_id');
                }
            });
        }

        if (Schema::hasTable('grades')) {
            Schema::table('grades', function (Blueprint $table) {
                if (Schema::hasColumn('grades', 'is_robot_activity')) {
                    $table->dropColumn('is_robot_activity');
                }
                if (Schema::hasColumn('grades', 'activity_category')) {
                    $table->dropColumn('activity_category');
                }
                if (Schema::hasColumn('grades', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'birth_date')) {
                    $table->dropColumn('birth_date');
                }
                if (Schema::hasColumn('students', 'first_name')) {
                    $table->dropColumn('first_name');
                }
                if (Schema::hasColumn('students', 'last_name')) {
                    $table->dropColumn('last_name');
                }
            });
        }
    }
};
