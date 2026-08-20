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
        Schema::table('mng_tooling_quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('mng_tooling_quotations', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('supplier_id')->index();
            }
            if (!Schema::hasColumn('mng_tooling_quotations', 'source_type')) {
                $table->string('source_type', 20)->default('supplier')->after('customer_id'); // 'supplier' or 'customer'
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_tooling_quotations', function (Blueprint $table) {
            if (Schema::hasColumn('mng_tooling_quotations', 'source_type')) {
                $table->dropColumn('source_type');
            }
            if (Schema::hasColumn('mng_tooling_quotations', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
        });
    }
};
