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
        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('inquiry_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('inquiry_id')->nullable(false)->change();
        });
    }
};
