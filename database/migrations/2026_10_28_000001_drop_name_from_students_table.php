<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El nombre del estudiante se guarda solo en sus partes (last_name, second_last_name,
 * first_name); el nombre completo lo arma el modelo Student. Se elimina la columna `name`.
 * Antes de borrarla, un registro antiguo que solo tuviera el nombre completo lo pasa a
 * first_name para no perderlo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'name')) {
            return;
        }

        DB::table('students')
            ->whereNotNull('name')->where('name', '<>', '')
            ->where(fn ($q) => $q->whereNull('first_name')->orWhere('first_name', ''))
            ->where(fn ($q) => $q->whereNull('last_name')->orWhere('last_name', ''))
            ->where(fn ($q) => $q->whereNull('second_last_name')->orWhere('second_last_name', ''))
            ->update(['first_name' => DB::raw('name')]);

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'name')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });

        DB::table('students')->orderBy('id')->chunkById(200, function ($students) {
            foreach ($students as $student) {
                $name = implode(' ', array_filter([$student->last_name, $student->second_last_name, $student->first_name]));
                DB::table('students')->where('id', $student->id)->update(['name' => $name]);
            }
        });
    }
};
