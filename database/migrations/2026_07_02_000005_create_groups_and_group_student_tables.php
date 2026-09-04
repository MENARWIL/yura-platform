<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('activity_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['group_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_student');
        Schema::dropIfExists('groups');
    }
};
