<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_project_inquiries', function (Blueprint $table) {
            $table->id('inquiry_id');
            $table->string('inquiry_no', 100)->unique();
            $table->string('customer_name', 255);
            $table->string('project_name', 255);
            $table->date('inquiry_date');
            $table->string('status', 50);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_project_inquiries');
    }
};
