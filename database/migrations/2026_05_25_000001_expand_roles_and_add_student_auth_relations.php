<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('padre')->change();
        });

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('usuario_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('student_family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('estudiantes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('relation_type', ['padre', 'madre', 'tutor']);
            $table->boolean('is_primary')->default(false);
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_receive_reports')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_family_members');

        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['padre', 'profesor', 'admin'])->default('padre')->change();
        });
    }
};
