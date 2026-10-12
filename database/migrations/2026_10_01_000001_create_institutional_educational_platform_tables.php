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
        // 1. Repositorio Oficial de Documentos, Normativas y Convocatorias
        if (!Schema::hasTable('official_documents')) {
            Schema::create('official_documents', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('code')->unique();
                $table->enum('category', ['resolucion', 'circular', 'convocatoria', 'comunicado', 'instructivo', 'reglamento'])->default('comunicado');
                $table->text('summary')->nullable();
                $table->unsignedSmallInteger('year');
                $table->string('file_path')->nullable();
                $table->date('published_at');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Catálogo de Servicios Educativos por Subsistema
        if (!Schema::hasTable('educational_services')) {
            Schema::create('educational_services', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('subsystem'); // regular, alternativa_especial, superior, docentes, estudiantes
                $table->text('description');
                $table->string('icon')->nullable();
                $table->string('action_url')->nullable();
                $table->string('target_audience')->nullable();
                $table->boolean('active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 3. Registro de Auditoría del Sistema
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->string('entity');
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('details')->nullable();
                $table->string('status', 20)->default('success');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('educational_services');
        Schema::dropIfExists('official_documents');
        // Tablas del módulo de trámites retirado: se mantienen aquí para que
        // un rollback limpie bases donde la versión anterior las creó.
        Schema::dropIfExists('procedure_attachments');
        Schema::dropIfExists('procedure_histories');
        Schema::dropIfExists('procedures');
        Schema::dropIfExists('procedure_types');
    }
};
