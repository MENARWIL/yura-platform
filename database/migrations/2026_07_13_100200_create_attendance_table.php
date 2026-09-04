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
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('courses')->cascadeOnDelete();
            $table->date('date');
            $table->string('status', 20); // presente, falta, licencia, retraso
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'date'], 'attendance_unique_student_subject_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
