<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grades', 'robot_activity_id')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->string('robot_activity_id', 100)->nullable()->after('is_robot_activity');
                $table->index('robot_activity_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grades', 'robot_activity_id')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropIndex(['robot_activity_id']);
                $table->dropColumn('robot_activity_id');
            });
        }
    }
};
