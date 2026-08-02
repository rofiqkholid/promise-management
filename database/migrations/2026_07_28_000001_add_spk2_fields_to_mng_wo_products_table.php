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
        Schema::table('mng_wo_products', function (Blueprint $table) {
            // Relasi ke master additional process di EBD (jika bersumber dari EBD)
            $table->foreignId('ebd_add_process_id')
                ->nullable()
                ->after('ebd_item_id')
                ->constrained('mng_ebd_add_processes')
                ->onDelete('set null');

            // Additional Process Snapshot Fields
            $table->string('add_process_name')->nullable()->after('ebd_add_process_id');
            $table->integer('add_process_qty')->nullable()->after('add_process_name');
            $table->string('add_process_unit')->nullable()->after('add_process_qty');

            // Material Spec Snapshot Fields (Tanpa Qty)
            $table->string('mat_spec')->nullable()->after('add_process_unit');
            $table->string('mat_size')->nullable()->after('mat_spec');
            $table->decimal('mat_weight_pcs', 10, 3)->nullable()->after('mat_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mng_wo_products', function (Blueprint $table) {
            $table->dropForeign(['ebd_add_process_id']);
            $table->dropColumn([
                'ebd_add_process_id',
                'add_process_name',
                'add_process_qty',
                'add_process_unit',
                'mat_spec',
                'mat_size',
                'mat_weight_pcs',
            ]);
        });
    }
};
