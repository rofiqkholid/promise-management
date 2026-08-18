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
        Schema::table('mng_ebd_tooling_processes', function (Blueprint $table) {
            if (!Schema::hasColumn('mng_ebd_tooling_processes', 'machine_type')) {
                $table->string('machine_type', 50)->default('Tandem')->nullable()->after('process_name');
            }
            if (!Schema::hasColumn('mng_ebd_tooling_processes', 'stroke')) {
                $table->decimal('stroke', 4, 2)->default(1.00)->nullable()->after('output_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_ebd_tooling_processes', function (Blueprint $table) {
            if (Schema::hasColumn('mng_ebd_tooling_processes', 'stroke')) {
                $table->dropColumn('stroke');
            }
            if (Schema::hasColumn('mng_ebd_tooling_processes', 'machine_type')) {
                $table->dropColumn('machine_type');
            }
        });
    }
};
