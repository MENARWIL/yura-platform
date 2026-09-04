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
        if (! Schema::hasTable('grades')) {
            return;
        }

        Schema::table('grades', function (Blueprint $table) {
            if (! Schema::hasColumn('grades', 'status')) {
                $table->string('status')->default('pending')->after('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('grades')) {
            return;
        }

        Schema::table('grades', function (Blueprint $table) {
            if (Schema::hasColumn('grades', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
