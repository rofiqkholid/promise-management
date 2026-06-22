<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_score_options', function (Blueprint $table) {
            $table->id('option_id');
            $table->unsignedBigInteger('category_id');
            $table->string('option_name', 150);
            $table->integer('score_value');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->foreign('category_id')
                ->references('category_id')
                ->on('mng_score_categories')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_score_options');
    }
};
