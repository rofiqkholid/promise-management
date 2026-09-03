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
        // 1. Add product summary fields to mng_tooling_quotations if not exist
        Schema::table('mng_tooling_quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('mng_tooling_quotations', 'quotation_type')) {
                $table->string('quotation_type', 20)->default('all')->after('source_type'); // 'all', 'product', 'tooling'
            }
            if (!Schema::hasColumn('mng_tooling_quotations', 'admin_matrl_pct')) {
                $table->decimal('admin_matrl_pct', 5, 2)->nullable()->after('exchange_rate');
            }
            if (!Schema::hasColumn('mng_tooling_quotations', 'admin_mfg_pct')) {
                $table->decimal('admin_mfg_pct', 5, 2)->nullable()->after('admin_matrl_pct');
            }
            if (!Schema::hasColumn('mng_tooling_quotations', 'oh_profit_pct')) {
                $table->decimal('oh_profit_pct', 5, 2)->nullable()->after('admin_mfg_pct');
            }
            if (!Schema::hasColumn('mng_tooling_quotations', 'total_material_cost')) {
                $table->decimal('total_material_cost', 18, 2)->default(0)->after('total_cost_idr');
            }
            if (!Schema::hasColumn('mng_tooling_quotations', 'total_mfg_cost')) {
                $table->decimal('total_mfg_cost', 18, 2)->default(0)->after('total_material_cost');
            }
            if (!Schema::hasColumn('mng_tooling_quotations', 'total_product_cogs')) {
                $table->decimal('total_product_cogs', 18, 2)->default(0)->after('total_mfg_cost');
            }
        });

        // 2. Create mng_product_quotation_details table for per-part product breakdown
        if (!Schema::hasTable('mng_product_quotation_details')) {
            Schema::create('mng_product_quotation_details', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('tooling_quotation_id')->index();
                $table->unsignedBigInteger('ebd_item_id')->nullable()->index();

                $table->string('part_no', 100)->nullable();
                $table->string('part_name', 200)->nullable();

                // Cost components
                $table->decimal('material_cost', 18, 2)->default(0);
                $table->decimal('stamping_cost', 18, 2)->default(0);
                $table->decimal('add_proc_cost', 18, 2)->default(0);
                $table->decimal('mfg_process_cost', 18, 2)->default(0);
                $table->decimal('cogm', 18, 2)->default(0);
                $table->decimal('cogs', 18, 2)->default(0);

                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('tooling_quotation_id')
                      ->references('id')
                      ->on('mng_tooling_quotations')
                      ->onDelete('cascade');

                $table->foreign('ebd_item_id')
                      ->references('id')
                      ->on('mng_ebd_items')
                      ->onDelete('no action');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_product_quotation_details');

        Schema::table('mng_tooling_quotations', function (Blueprint $table) {
            $cols = [
                'quotation_type',
                'admin_matrl_pct',
                'admin_mfg_pct',
                'oh_profit_pct',
                'total_material_cost',
                'total_mfg_cost',
                'total_product_cogs'
            ];
            foreach ($cols as $c) {
                if (Schema::hasColumn('mng_tooling_quotations', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
