<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('robot_activities', function (Blueprint $table) {
            $table->unsignedInteger('attempt_number')->default(0);
            $table->uuid('current_attempt_id')->nullable()->unique();
        });

        Schema::create('robot_activity_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('robot_activity_id')
                ->constrained('robot_activities')
                ->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->uuid('attempt_id')->unique();
            $table->string('status', 20)->default('in_progress')->index();
            $table->json('result_payload')->nullable();
            $table->string('result_hash', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['robot_activity_id', 'attempt_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('robot_activity_attempts');

        Schema::table('robot_activities', function (Blueprint $table) {
            $table->dropUnique(['current_attempt_id']);
            $table->dropColumn(['attempt_number', 'current_attempt_id']);
        });
    }
};
