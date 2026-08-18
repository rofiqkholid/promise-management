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
        Schema::create('mng_mfg_process_costs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('Product'); // Product, Tooling
            $table->string('process_group'); // Stamping, Non Stamping, Others, etc.
            $table->string('process_name'); // Stamping Rate, RSW, PSW, SSW, etc.
            $table->string('control_point')->nullable(); // Tonnage - Stroke - Part Criteria, Qty Spot, Cycle time, etc.
            $table->string('uom')->nullable(); // Stroke, Spot, second, mm, mm2, etc.
            $table->string('rate_unit')->nullable(); // Idr / stroke, Idr / spot, Idr / second, etc.
            $table->decimal('min_cost_rate', 15, 2)->nullable(); // Min rate (optional)
            $table->decimal('std_cost_rate', 15, 2); // Std rate (wajib)
            $table->string('rate_source')->default('Sales'); // Engineering, Sales
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_mfg_process_costs');
    }
};
