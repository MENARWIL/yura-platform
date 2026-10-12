<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina las 7 tablas de categorías del robot (robot_alimentos, robot_colores,
 * robot_casa_items, robot_naturaleza_items, robot_familia_items,
 * robot_herramientas_items, robot_lugares_items), creadas en
 * 2026_07_20_000002_create_robot_category_tables.php pero nunca usadas como
 * fuente de datos real: confirmado que las 7 tienen 0 filas, ninguna llave
 * foránea entrante ni saliente, y el código (GradeController::createRobotActivity/
 * RobotActivityController::create) solo referencia sus nombres como strings
 * literales dentro de un arreglo de categorías para un <select>, sin consultarlas
 * nunca mediante Eloquent ni el query builder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('robot_alimentos');
        Schema::dropIfExists('robot_colores');
        Schema::dropIfExists('robot_casa_items');
        Schema::dropIfExists('robot_naturaleza_items');
        Schema::dropIfExists('robot_familia_items');
        Schema::dropIfExists('robot_herramientas_items');
        Schema::dropIfExists('robot_lugares_items');
    }

    public function down(): void
    {
        // Sin rollback: las 7 tablas estaban huérfanas (0 registros, sin código
        // que las consultara); si se necesita un catálogo real de categorías del
        // robot en el futuro, debe diseñarse con una migración nueva en lugar de
        // recrear estas tablas vacías.
    }
};
