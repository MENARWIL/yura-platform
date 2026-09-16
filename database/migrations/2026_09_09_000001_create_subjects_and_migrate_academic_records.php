<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 30)->nullable()->unique();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $quechuaId = DB::table('subjects')->insertGetId([
            'name' => 'Quechua',
            'code' => 'QUE',
            'active' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign(['subject_id']);
        });

        DB::table('grades')->update(['subject_id' => $quechuaId]);
        DB::table('attendance')->update(['subject_id' => $quechuaId]);

        Schema::table('grades', function (Blueprint $table) {
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
        Schema::table('attendance', function (Blueprint $table) {
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        throw new \RuntimeException('La migración de asignaturas no se puede revertir sin perder la relación histórica de calificaciones y asistencias.');
    }
};