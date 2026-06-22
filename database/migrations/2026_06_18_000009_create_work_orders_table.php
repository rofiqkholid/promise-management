<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_work_orders', function (Blueprint $table) {
            $table->id('work_order_id');
            $table->unsignedBigInteger('inquiry_id');
            $table->string('work_order_no', 100);
            $table->integer('revision_no')->default(0);
            $table->unsignedBigInteger('revised_from_id')->nullable();
            $table->boolean('is_latest')->default(true);
            $table->unsignedInteger('department_id'); // Owner department
            $table->string('priority', 50)->nullable();
            $table->string('subject', 255);
            $table->string('status', 50);
            $table->text('remarks')->nullable();
            $table->string('created_by', 100);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['work_order_no', 'revision_no']);

            $table->foreign('inquiry_id')
                ->references('inquiry_id')
                ->on('mng_project_inquiries')
                ->onDelete('cascade');

            $table->foreign('revised_from_id')
                ->references('work_order_id')
                ->on('mng_work_orders')
                ->onDelete('no action');

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_work_orders');
    }
};
