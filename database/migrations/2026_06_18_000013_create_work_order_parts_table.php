<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_work_order_parts', function (Blueprint $table) {
            $table->id('work_order_part_id');
            $table->unsignedBigInteger('work_order_product_id');
            $table->string('eo', 100)->nullable();
            $table->string('part_no', 100);
            $table->string('part_name', 255);
            $table->string('class_id', 100)->nullable();
            $table->string('uom', 50)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_order_product_id')
                ->references('work_order_product_id')
                ->on('mng_work_order_products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_work_order_parts');
    }
};
