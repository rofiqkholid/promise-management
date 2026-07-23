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
        Schema::table('mng_ebd_items', function (Blueprint $table) {
            if (!Schema::hasColumn('mng_ebd_items', 'std_part_name')) {
                $table->string('std_part_name', 255)->nullable();
            }
        });

        Schema::table('mng_ebd_tooling_processes', function (Blueprint $table) {
            if (Schema::hasColumn('mng_ebd_tooling_processes', 'cavity') && !Schema::hasColumn('mng_ebd_tooling_processes', 'output')) {
                $table->renameColumn('cavity', 'output');
            } elseif (!Schema::hasColumn('mng_ebd_tooling_processes', 'output')) {
                $table->integer('output')->nullable();
            }

            if (!Schema::hasColumn('mng_ebd_tooling_processes', 'output_type')) {
                $table->string('output_type', 50)->nullable();
            }

            $table->integer('op')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_ebd_items', function (Blueprint $table) {
            if (Schema::hasColumn('mng_ebd_items', 'std_part_name')) {
                $table->dropColumn('std_part_name');
            }
        });

        Schema::table('mng_ebd_tooling_processes', function (Blueprint $table) {
            if (Schema::hasColumn('mng_ebd_tooling_processes', 'output_type')) {
                $table->dropColumn('output_type');
            }

            if (Schema::hasColumn('mng_ebd_tooling_processes', 'output') && !Schema::hasColumn('mng_ebd_tooling_processes', 'cavity')) {
                $table->renameColumn('output', 'cavity');
            }

            $table->string('op', 20)->nullable()->change();
        });
    }
};
