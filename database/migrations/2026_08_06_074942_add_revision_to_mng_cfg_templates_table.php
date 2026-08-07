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
        Schema::table('mng_cfg_templates', function (Blueprint $table) {
            $table->string('revision', 20)->default('0')->after('template_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_cfg_templates', function (Blueprint $table) {
            $table->dropColumn('revision');
        });
    }
};
