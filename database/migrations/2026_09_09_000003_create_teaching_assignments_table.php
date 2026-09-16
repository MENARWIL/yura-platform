<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('parallel_id')->constrained('parallels')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['subject_id', 'parallel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
    }
};