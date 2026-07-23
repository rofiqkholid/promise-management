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
            $table->text('urgent_reason')->nullable()->after('priority');
            $table->string('urgent_confirmed_by', 255)->nullable()->after('urgent_reason');
            $table->timestamp('urgent_confirmed_at')->nullable()->after('urgent_confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_work_orders', function (Blueprint $table) {
            $table->dropColumn(['urgent_reason', 'urgent_confirmed_by', 'urgent_confirmed_at']);
        });
    }
};
