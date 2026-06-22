<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_work_order_processes', function (Blueprint $table) {
            $table->id('process_id');
            $table->string('process_code', 50)->unique();
            $table->string('process_name', 150);
            $table->unsignedInteger('owner_department_id');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->nullable();

            $table->foreign('owner_department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_work_order_processes');
    }
};
