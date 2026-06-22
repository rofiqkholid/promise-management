<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_work_order_products', function (Blueprint $table) {
            $table->id('work_order_product_id');
            $table->unsignedBigInteger('work_order_id');
            $table->unsignedBigInteger('inquiry_product_id');
            $table->string('customer_name', 255);
            $table->string('model_name', 255);
            $table->string('customer_part_no', 100);
            $table->string('customer_part_name', 255);
            $table->string('destination', 100)->nullable();
            $table->date('sop_date')->nullable();
            $table->date('eol_date')->nullable();
            $table->integer('model_life')->nullable();
            $table->integer('annual_volume')->nullable();
            $table->date('first_sample_date')->nullable();
            $table->date('due_date_approval')->nullable();
            $table->date('due_date_closed')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('work_order_id')
                ->references('work_order_id')
                ->on('mng_work_orders')
                ->onDelete('cascade');

            $table->foreign('inquiry_product_id')
                ->references('inquiry_product_id')
                ->on('mng_inquiry_products')
                ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_work_order_products');
    }
};
