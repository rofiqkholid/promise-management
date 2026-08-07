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
        Schema::create('mng_cfg_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_type')->index();       // e.g. 'quotation', 'purchase_order', 'invoice'
            $table->unsignedBigInteger('customer_id')->nullable(); // Nullable jika template bertipe umum/vendor
            $table->string('template_name');                // e.g. "Quotation Fabricated Part Honda"
            $table->string('file_path');                    // Storage path master .xlsx
            $table->json('mapping_config')->nullable();     // Generated JSON from Interactive Web UI Mapper
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_cfg_templates');
    }
};
