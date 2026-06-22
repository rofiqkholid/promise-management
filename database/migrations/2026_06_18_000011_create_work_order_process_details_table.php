<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_work_order_process_details', function (Blueprint $table) {
            $table->id('process_detail_id');
            $table->unsignedBigInteger('work_order_id');
            $table->unsignedBigInteger('process_id');
            $table->text('remarks')->nullable();

            $table->foreign('work_order_id')
                ->references('work_order_id')
                ->on('mng_work_orders')
                ->onDelete('cascade');

            $table->foreign('process_id')
                ->references('process_id')
                ->on('mng_work_order_processes')
                ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_work_order_process_details');
    }
};
