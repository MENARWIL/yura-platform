<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        if (Schema::hasTable('student_tutor')) {
            Schema::dropIfExists('student_tutor');
        }

        if (Schema::hasTable('tutors')) {
            Schema::dropIfExists('tutors');
        }

        if (Schema::hasTable('estudiantes')) {
            Schema::dropIfExists('estudiantes');
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // No-op: this migration is intentionally destructive for legacy tables.
    }
};
