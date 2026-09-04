<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('activity_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->text('question');
            $table->text('answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('response_time')->default(0);
            $table->timestamps();
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('activity_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->text('feedback')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
        Schema::dropIfExists('interactions');
    }
};
