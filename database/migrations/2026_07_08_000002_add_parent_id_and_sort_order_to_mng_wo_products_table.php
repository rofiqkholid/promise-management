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
        Schema::table('mng_wo_products', function (Blueprint $table) {
            // Make inquiry_product_id nullable
            $table->unsignedBigInteger('inquiry_product_id')->nullable()->change();
            
            // Add parent_id and sort_order
            $table->foreignId('parent_id')->nullable()->constrained('mng_wo_products')->noActionOnDelete();
            $table->integer('sort_order')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_wo_products', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id', 'sort_order']);
            $table->unsignedBigInteger('inquiry_product_id')->nullable(false)->change();
        });
    }
};
