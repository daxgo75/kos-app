<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update enum values for status column
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'paid', 'overdue', 'partial') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid'");
    }
};
