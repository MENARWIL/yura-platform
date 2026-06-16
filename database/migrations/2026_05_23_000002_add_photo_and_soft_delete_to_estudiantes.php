<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            if (! Schema::hasColumn('estudiantes', 'foto_path')) {
                $table->string('foto_path')->nullable()->after('estado');
            }

            if (! Schema::hasColumn('estudiantes', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            if (Schema::hasColumn('estudiantes', 'foto_path')) {
                $table->dropColumn('foto_path');
            }

            if (Schema::hasColumn('estudiantes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
