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
        Schema::table('grades', function (Blueprint $table) {
            if (! Schema::hasColumn('grades', 'score')) {
                $table->integer('score')->default(0)->after('status')->comment('Nota de la actividad específica');
            }
            if (! Schema::hasColumn('grades', 'activity_number')) {
                $table->tinyInteger('activity_number')->default(1)->after('score')->comment('Número de actividad: 1, 2 o 3');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            if (Schema::hasColumn('grades', 'activity_number')) {
                $table->dropColumn('activity_number');
            }
            if (Schema::hasColumn('grades', 'score')) {
                $table->dropColumn('score');
            }
        });
    }
};
