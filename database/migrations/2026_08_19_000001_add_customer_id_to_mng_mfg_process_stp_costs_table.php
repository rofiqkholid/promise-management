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
        Schema::table('mng_mfg_process_stp_costs', function (Blueprint $table) {
            if (!Schema::hasColumn('mng_mfg_process_stp_costs', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_mfg_process_stp_costs', function (Blueprint $table) {
            if (Schema::hasColumn('mng_mfg_process_stp_costs', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
        });
    }
};
