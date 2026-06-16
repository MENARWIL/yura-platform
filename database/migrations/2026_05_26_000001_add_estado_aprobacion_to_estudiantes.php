<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            if (! Schema::hasColumn('estudiantes', 'estado_aprobacion')) {
                $table->string('estado_aprobacion')->default('pendiente')->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estudiantes', function (Blueprint $table) {
            if (Schema::hasColumn('estudiantes', 'estado_aprobacion')) {
                $table->dropColumn('estado_aprobacion');
            }
        });
    }
};
