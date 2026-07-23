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
        // 1. Update mng_work_orders header table
        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->string('wo_type', 50)->default('SPK_1');
            $table->foreignId('ebd_header_id')
                  ->nullable()
                  ->constrained('mng_ebd_headers')
                  ->onDelete('no action');
        });

        // 2. Update mng_wo_products detail table
        Schema::table('mng_wo_products', function (Blueprint $table) {
            $table->unsignedBigInteger('inquiry_product_id')->nullable()->change();
            $table->foreignId('ebd_item_id')
                  ->nullable()
                  ->constrained('mng_ebd_items')
                  ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_wo_products', function (Blueprint $table) {
            $table->dropForeign(['ebd_item_id']);
            $table->dropColumn('ebd_item_id');
            $table->unsignedBigInteger('inquiry_product_id')->nullable(false)->change();
        });

        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->dropForeign(['ebd_header_id']);
            $table->dropColumn(['wo_type', 'ebd_header_id']);
        });
    }
};
