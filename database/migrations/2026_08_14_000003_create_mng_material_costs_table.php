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
        Schema::create('mng_material_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable(); // Null = Umum, Terisi = Khusus Customer

            // 1. Identitas & Karakteristik Material
            $table->string('material_spec');                         // SPCC, SPHC, SECC, JAC270D, SUS304, dll.
            $table->string('material_type')->default('Sheet');        // Coil, Sheet (Bahan baku stamping)
            $table->decimal('thickness', 8, 2)->nullable();          // Tebal plat (mm)

            // 2. Harga Dasar (Rate)
            $table->decimal('price_per_kg', 15, 2);                  // Harga beli per Kg (IDR)
            $table->decimal('scrap_price_per_kg', 15, 2)->default(0); // Harga jual limbah potongan/scrap per Kg

            // 3. Sumber, Kebijakan Customer & Periode Berlaku
            $table->string('rate_source')->default('Sales');    // Engineering, Sales
            $table->date('valid_from')->nullable();                  // Mulai berlaku

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['material_spec', 'material_type', 'rate_source', 'is_active'], 'material_rate_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_material_costs');
    }
};
