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
        Schema::create('mng_mfg_process_stp_costs', function (Blueprint $table) {
            $table->id();

            // 1. Identitas Mesin
            $table->string('machine_type')->default('Tandem');
            $table->integer('tonnage');
            $table->string('machine_category')->nullable(); // Small, Medium, Large

            // 2. Konfigurasi Output & Setup
            $table->string('output_type')->default('Part'); // Part, Cavity, Process
            $table->integer('output_qty')->default(1);
            $table->decimal('stroke', 4, 2)->default(1.00);

            // 3. Kategori Kesulitan & Alias
            $table->string('process_complexity')->default('Inner'); // Inner, Deep Draw, Outer Panel, dll.
            $table->string('complexity_alias')->nullable(); // Alias / Part Rank (A, B, C, D, dsb.)

            // 4. Tarif Biaya
            $table->decimal('min_cost_rate', 15, 2)->nullable();
            $table->decimal('std_cost_rate', 15, 2);

            // 5. Metadata
            $table->string('rate_source')->default('Sales');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['machine_type', 'tonnage', 'output_type', 'process_complexity'], 'stamping_rate_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_mfg_process_stp_costs');
    }
};
