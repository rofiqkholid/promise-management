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
        Schema::create('mng_cfg_system_fields', function (Blueprint $table) {
            $table->id();
            $table->string('field_key')->unique();  // Unique identifier (e.g. 'rfq_no', 'part_number')
            $table->string('label');                // Human-readable label for UI Dropdown
            $table->string('group');                // UI Grouping (e.g. 'Header', 'Detail', 'Material', 'Process')
            $table->enum('data_type', ['string', 'numeric', 'decimal', 'date', 'boolean'])->default('string');
            
            // Physical Database Mapping
            $table->string('target_table')->nullable();  // e.g. 'mng_tooling_quotations'
            $table->string('target_column')->nullable(); // e.g. 'rfq_no'
            
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_cfg_system_fields');
    }
};
