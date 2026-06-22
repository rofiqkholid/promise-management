<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_assessment_rankings', function (Blueprint $table) {
            $table->id('ranking_id');
            $table->string('rank_code', 50);
            $table->integer('min_score');
            $table->integer('max_score');
            $table->string('priority_label', 100)->nullable();
            $table->text('recommendation')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_assessment_rankings');
    }
};
