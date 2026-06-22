<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_priority_assessment_details', function (Blueprint $table) {
            $table->id('detail_id');
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('option_id');
            $table->integer('score_snapshot');
            $table->text('remarks')->nullable();

            $table->foreign('assessment_id')
                ->references('assessment_id')
                ->on('mng_priority_assessments')
                ->onDelete('cascade');

            $table->foreign('category_id')
                ->references('category_id')
                ->on('mng_score_categories')
                ->onDelete('no action');

            $table->foreign('option_id')
                ->references('option_id')
                ->on('mng_score_options')
                ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_priority_assessment_details');
    }
};
