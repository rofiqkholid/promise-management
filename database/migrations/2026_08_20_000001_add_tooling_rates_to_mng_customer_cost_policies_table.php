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
        Schema::table('mng_customer_cost_policies', function (Blueprint $table) {
            $table->decimal('tooling_oh_profit_pct', 5, 2)->default(20.00)->after('min_std_margin_pct');
            $table->decimal('tooling_min_std_margin_pct', 5, 2)->default(20.00)->after('tooling_oh_profit_pct');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_customer_cost_policies', function (Blueprint $table) {
            $table->dropColumn(['tooling_oh_profit_pct', 'tooling_min_std_margin_pct']);
        });
    }
};
