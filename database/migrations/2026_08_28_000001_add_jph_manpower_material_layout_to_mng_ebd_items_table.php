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
        // 1. Add material_layout to mng_ebd_items
        Schema::table('mng_ebd_items', function (Blueprint $table) {
            if (!Schema::hasColumn('mng_ebd_items', 'material_layout')) {
                $table->string('material_layout', 255)->nullable()->after('sketch');
            }
        });

        // 2. Add jph_gsph and man_power to mng_ebd_tooling_processes
        Schema::table('mng_ebd_tooling_processes', function (Blueprint $table) {
            if (!Schema::hasColumn('mng_ebd_tooling_processes', 'jph_gsph')) {
                $table->decimal('jph_gsph', 10, 2)->nullable()->after('stroke');
            }
            if (!Schema::hasColumn('mng_ebd_tooling_processes', 'man_power')) {
                $table->decimal('man_power', 8, 2)->nullable()->after('jph_gsph');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_ebd_tooling_processes', function (Blueprint $table) {
            if (Schema::hasColumn('mng_ebd_tooling_processes', 'man_power')) {
                $table->dropColumn('man_power');
            }
            if (Schema::hasColumn('mng_ebd_tooling_processes', 'jph_gsph')) {
                $table->dropColumn('jph_gsph');
            }
        });

        Schema::table('mng_ebd_items', function (Blueprint $table) {
            if (Schema::hasColumn('mng_ebd_items', 'material_layout')) {
                $table->dropColumn('material_layout');
            }
        });
    }
};
