<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina un subsistema completo (interactions, results, groups, group_student,
 * activity_sessions, activities, class_student, classes) sin ninguna referencia
 * real en controladores, vistas, rutas, seeders ni tests (confirmado por auditoría
 * de base de datos, docs/pruebas/Informe_Auditoria_Base_de_Datos_YURA.docx).
 * Los modelos Eloquent correspondientes (Activity, ActivitySession, SchoolClass,
 * Group, Interaction, Result) fueron eliminados en el mismo cambio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('interactions');
        Schema::dropIfExists('results');
        Schema::dropIfExists('group_student');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('activity_sessions');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('class_student');
        Schema::dropIfExists('classes');
    }

    public function down(): void
    {
        // Sin rollback: este subsistema estaba huérfano (0 registros, sin código
        // que lo use); si se necesita en el futuro, debe rediseñarse con una
        // migración nueva en lugar de recrear el esquema legado.
    }
};
