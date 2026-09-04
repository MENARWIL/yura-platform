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
        // Migration created as placeholder for future cleanup of truly duplicate columns
        // The 'course' field is a legacy denormalized field used throughout the application
        // A full refactoring to remove it would require updates to:
        // - StudentController (store method)
        // - DatabaseSeeder
        // - Tests (FamilyAndStudentAuthTest)
        // - StudentsExport
        // - Views (reports/students_pdf.blade.php)
        // This can be done in a future iteration when those components are refactored
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed - this migration is a no-op placeholder
    }
};
