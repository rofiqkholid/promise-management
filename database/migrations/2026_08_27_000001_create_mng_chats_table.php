<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mng_chats', function (Blueprint $table) {
            $table->id();
            $table->string('chatable_type', 100);
            $table->unsignedBigInteger('chatable_id');
            $table->integer('user_id');
            $table->unsignedBigInteger('reply_to_id')->nullable();
            $table->text('message')->nullable();
            $table->json('tagged_user_ids')->nullable();
            $table->json('tagged_items')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_type', 100)->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('reply_to_id')
                ->references('id')
                ->on('mng_chats')
                ->onDelete('no action');

            // Polymorphic & query indexing
            $table->index(['chatable_type', 'chatable_id', 'id'], 'idx_chatable_lookup');
            $table->index('reply_to_id', 'idx_reply_to_id');
        });

        // Migrate existing inquiry product chats if table exists
        if (Schema::hasTable('mng_inquiry_product_chats')) {
            $existing = DB::table('mng_inquiry_product_chats')->get();
            foreach ($existing as $oldChat) {
                DB::table('mng_chats')->insert([
                    'chatable_type' => 'inquiry_product',
                    'chatable_id' => $oldChat->inquiry_product_id,
                    'user_id' => $oldChat->user_id,
                    'reply_to_id' => null,
                    'message' => $oldChat->message,
                    'tagged_user_ids' => null,
                    'tagged_items' => null,
                    'file_path' => $oldChat->file_path,
                    'file_name' => $oldChat->file_name,
                    'file_type' => $oldChat->file_type,
                    'file_size' => $oldChat->file_size,
                    'created_at' => $oldChat->created_at ?? now(),
                    'updated_at' => $oldChat->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_chats');
    }
};
