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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->date('check_in_date');
            $table->date('check_out_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'moved_out'])->default('active');
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('room_id');
            $table->index('status');
            $table->index(['room_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
