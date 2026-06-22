<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_work_order_departments', function (Blueprint $table) {
            $table->id('work_order_department_id');
            $table->unsignedBigInteger('work_order_id');
            $table->unsignedInteger('department_id'); // Support department
            $table->text('remarks')->nullable();

            $table->foreign('work_order_id')
                ->references('work_order_id')
                ->on('mng_work_orders')
                ->onDelete('cascade');

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_work_order_departments');
    }
};
