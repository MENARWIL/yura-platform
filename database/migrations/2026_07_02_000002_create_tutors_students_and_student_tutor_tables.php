<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ci')->unique();
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('course');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('student_tutor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained('tutors')->cascadeOnDelete();
            $table->string('relationship');
            $table->timestamps();
            $table->unique(['student_id', 'tutor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_tutor');
        Schema::dropIfExists('students');
        Schema::dropIfExists('tutors');
    }
};
