<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('robot_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('parallel_id')->constrained('parallels')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');
            $table->unsignedTinyInteger('activity_number');
            $table->string('status', 20)->default('pending')->index();
            $table->json('payload');
            $table->json('result_payload')->nullable();
            $table->string('result_hash', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_activities');
    }
};
