<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_work_order_attachments', function (Blueprint $table) {
            $table->id('attachment_id');
            $table->unsignedBigInteger('work_order_id');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('uploaded_by', 100);
            $table->timestamp('uploaded_at')->nullable();

            $table->foreign('work_order_id')
                ->references('work_order_id')
                ->on('mng_work_orders')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_work_order_attachments');
    }
};
