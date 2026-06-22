<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_inquiry_products', function (Blueprint $table) {
            $table->id('inquiry_product_id');
            $table->unsignedBigInteger('inquiry_id');
            $table->string('model_name', 255);
            $table->string('customer_part_no', 100);
            $table->string('customer_part_name', 255);
            $table->string('part_category', 100)->nullable();
            $table->string('destination', 100)->nullable();
            $table->date('sop_date')->nullable();
            $table->date('eol_date')->nullable();
            $table->integer('model_life')->nullable();
            $table->integer('annual_volume')->nullable();
            $table->boolean('has_2d_data')->default(false);
            $table->boolean('has_3d_data')->default(false);
            $table->boolean('has_tech_doc')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inquiry_id')
                ->references('inquiry_id')
                ->on('mng_project_inquiries')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_inquiry_products');
    }
};
