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
            $table->string('document_no', 100)->default('FO-13-02')->nullable();
            $table->string('doc_department', 100)->default('Sales')->nullable();
            $table->date('publish_date')->nullable();
            $table->string('page_hal', 50)->default('1')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->dropColumn(['document_no', 'doc_department', 'publish_date', 'page_hal']);
        });
    }
};
