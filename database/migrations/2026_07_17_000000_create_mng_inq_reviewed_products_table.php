<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_inq_reviewed_products', function (Blueprint $table) {
            $table->id();
            $table->string('reviewer', 255);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('mng_inquiry_products', function (Blueprint $table) {
            $table->foreignId('reviewed_product_id')
                ->nullable()
                ->constrained('mng_inq_reviewed_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mng_inquiry_products', function (Blueprint $table) {
            $table->dropForeign(['reviewed_product_id']);
            $table->dropColumn('reviewed_product_id');
        });

        Schema::dropIfExists('mng_inq_reviewed_products');
    }
};
