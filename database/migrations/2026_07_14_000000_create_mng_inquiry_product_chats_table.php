<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mng_inquiry_product_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_product_id')
                ->constrained('mng_inquiry_products')
                ->onDelete('cascade');
            $table->integer('user_id');
            $table->text('message')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_type', 100)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();

            // Foreign key to users
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Optimal indexing
            $table->index(['inquiry_product_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_inquiry_product_chats');
    }
};
