<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->text('request_types')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->dropColumn('request_types');
        });
    }
};
