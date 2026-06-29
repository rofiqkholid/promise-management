<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('inquiry_no', 100)->unique();
            $table->integer('customer_id')->nullable();
            $table->string('project_name', 255);
            $table->integer('model_id')->nullable();
            $table->date('inquiry_date');
            $table->string('status', 50);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mng_inquiry_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')
                ->constrained('mng_inquiries')
                ->onDelete('cascade');
            $table->string('variant', 100)->nullable();
            $table->string('customer_part_no', 100);
            $table->string('customer_part_name', 255);
            $table->string('part_category', 100)->nullable();
            $table->string('destination', 100)->nullable();
            $table->date('sop_date')->nullable();
            $table->date('eol_date')->nullable();
            $table->integer('model_life')->nullable();
            $table->integer('annual_volume')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('has_2d_data')->default(false);
            $table->boolean('has_3d_data')->default(false);
            $table->boolean('has_tech_doc')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_inquiry_products');
        Schema::dropIfExists('mng_inquiries');
    }
};
