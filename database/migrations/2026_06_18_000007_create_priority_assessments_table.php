<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_priority_assessments', function (Blueprint $table) {
            $table->id('assessment_id');
            $table->unsignedBigInteger('inquiry_product_id');
            $table->integer('total_score');
            $table->unsignedBigInteger('ranking_id');
            $table->string('action', 100);
            $table->string('action_override', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->string('assessed_by', 100);
            $table->timestamp('assessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inquiry_product_id')
                ->references('inquiry_product_id')
                ->on('mng_inquiry_products')
                ->onDelete('cascade');

            $table->foreign('ranking_id')
                ->references('ranking_id')
                ->on('mng_assessment_rankings')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_priority_assessments');
    }
};
