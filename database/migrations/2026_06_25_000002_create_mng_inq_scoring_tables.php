<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_inq_score_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_code', 100)->unique();
            $table->string('category_name', 255);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mng_inq_score_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('mng_inq_score_categories')
                ->onDelete('cascade');
            $table->string('option_name', 255);
            $table->integer('score_value');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('mng_inq_rankings', function (Blueprint $table) {
            $table->id();
            $table->string('rank_code', 50);
            $table->integer('min_score');
            $table->integer('max_score');
            $table->string('priority_label', 100);
            $table->text('recommendation')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mng_inq_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_product_id')
                ->constrained('mng_inquiry_products')
                ->onDelete('cascade');
            $table->integer('total_score');
            $table->foreignId('ranking_id')
                ->constrained('mng_inq_rankings')
                ->onDelete('cascade');
            $table->string('action', 100)->nullable();
            $table->text('remarks')->nullable();
            $table->string('assessed_by', 100)->nullable();
            $table->datetime('assessed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mng_inq_assessment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')
                ->constrained('mng_inq_assessments')
                ->onDelete('cascade');
            $table->foreignId('category_id')
                ->constrained('mng_inq_score_categories')
                ->onDelete('no action');
            $table->foreignId('option_id')
                ->constrained('mng_inq_score_options')
                ->onDelete('no action');
            $table->integer('score_snapshot');
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_inq_assessment_details');
        Schema::dropIfExists('mng_inq_assessments');
        Schema::dropIfExists('mng_inq_rankings');
        Schema::dropIfExists('mng_inq_score_options');
        Schema::dropIfExists('mng_inq_score_categories');
    }
};
