<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'robot_alimentos',
            'robot_colores',
            'robot_casa_items',
            'robot_naturaleza_items',
            'robot_familia_items',
            'robot_herramientas_items',
            'robot_lugares_items',
        ];

        foreach ($tables as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'robot_lugares_items',
            'robot_herramientas_items',
            'robot_familia_items',
            'robot_naturaleza_items',
            'robot_casa_items',
            'robot_colores',
            'robot_alimentos',
        ];

        foreach ($tables as $tableName) {
            Schema::dropIfExists($tableName);
        }
    }
};
