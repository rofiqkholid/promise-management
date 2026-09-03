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
        Schema::table('mng_ebd_headers', function (Blueprint $table) {
            $table->unsignedBigInteger('revised_from_id')->nullable()->after('wo_id');
            $table->boolean('is_latest')->default(true)->after('revision');
            $table->text('revision_note')->nullable()->after('status');

            $table->foreign('revised_from_id')
                ->references('id')
                ->on('mng_ebd_headers')
                ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_ebd_headers', function (Blueprint $table) {
            $table->dropForeign(['revised_from_id']);
            $table->dropColumn(['revised_from_id', 'is_latest', 'revision_note']);
        });
    }
};
