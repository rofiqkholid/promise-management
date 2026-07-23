<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================================
        // TABLE 1: EBD HEADERS — Administrative document header per EBD file
        // =====================================================================
        Schema::create('mng_ebd_headers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wo_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('model_id')->nullable();
            $table->date('date')->nullable();
            $table->string('revision', 20)->default('0');
            $table->string('status', 50)->default('Draft'); // Draft | Released
            $table->string('created_by', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('wo_id')
                ->references('id')
                ->on('mng_work_orders')
                ->onDelete('set null');
        });

        // =====================================================================
        // TABLE 2: EBD ITEMS — Master BOM component with dynamic self-reference
        // =====================================================================
        Schema::create('mng_ebd_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebd_header_id')
                ->constrained('mng_ebd_headers')
                ->onDelete('cascade');

            // Self-reference for dynamic BOM hierarchy
            $table->unsignedBigInteger('parent_id')->nullable()
                ->comment('Self-reference for dynamic BOM hierarchy');
            $table->integer('active_level')->nullable()
                ->comment('Stores the level number when imported (1, 2, 3, etc.)');

            // Part Identification
            $table->string('part_no', 100);
            $table->string('part_name', 255);
            $table->integer('pcs_month')->nullable();
            $table->string('sketch', 255)->nullable();

            // Part Dimensions
            $table->integer('qty_unit')->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('weight', 10, 3)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('part_rank', 10)->nullable();

            // Material Specification
            $table->string('mat_spec', 100)->nullable();
            $table->decimal('mat_thick', 5, 2)->nullable();
            $table->decimal('mat_width', 10, 2)->nullable();
            $table->decimal('mat_length', 10, 2)->nullable();
            $table->integer('mat_pcs_sheet')->nullable();
            $table->decimal('mat_weight_pcs', 10, 3)->nullable();
            $table->decimal('mat_yield_ratio', 5, 2)->nullable();

            // Standard Part (merged into master item table)
            $table->string('std_part_no', 100)->nullable()
                ->comment('Part No for standard/buy-out components');
            $table->string('std_part_name', 255)->nullable()
                ->comment('Name for standard/buy-out components');
            $table->integer('std_qty')->nullable()
                ->comment('Required quantity for standard components');
            $table->string('std_uom', 50)->nullable()
                ->comment('Standard Part Unit of Measure');

            // Packing Cost
            $table->string('packing_type', 50)->nullable();
            $table->integer('pcs_packing')->nullable();

            // Transport Cost
            $table->decimal('part_vol_m2', 10, 4)->nullable();
            $table->decimal('truck_vol_m2', 10, 4)->nullable();

            $table->timestamps();

            // Self-referential FK — SQL Server does not allow CASCADE on a self-ref FK
            // when another CASCADE path exists. Orphan children are handled in the Model.
            $table->foreign('parent_id')
                ->references('id')
                ->on('mng_ebd_items')
                ->onDelete('no action');
        });

        // =====================================================================
        // TABLE 3: EBD TOOLING PROCESSES — Multi-row stamping process per item
        // =====================================================================
        Schema::create('mng_ebd_tooling_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebd_item_id')
                ->constrained('mng_ebd_items')
                ->onDelete('cascade');

            $table->string('tool_rank', 10)->nullable();
            $table->string('category', 50)->nullable();
            $table->integer('op')->nullable();
            $table->string('process_name', 100)->nullable();
            $table->string('prod_homeline', 50)->nullable();
            $table->integer('tonnage')->nullable()
                ->comment('Null if Level 1 or CF/Jig process');
            $table->decimal('die_height', 10, 2)->nullable()
                ->comment('Null if Level 1 or CF/Jig process');
            $table->integer('output')->nullable();
            $table->string('output_type', 50)->nullable();
            $table->integer('qty')->nullable();
            $table->decimal('price_idr', 15, 2)->nullable();
            $table->string('tooling_status', 50)->nullable();
            $table->text('information')->nullable();

            $table->timestamps();
        });

        // =====================================================================
        // TABLE 4: EBD ADD PROCESSES — Multi-row secondary process per item
        // =====================================================================
        Schema::create('mng_ebd_add_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebd_item_id')
                ->constrained('mng_ebd_items')
                ->onDelete('cascade');

            $table->string('process_name', 100)->nullable();
            $table->integer('qty')->nullable();
            $table->string('unit', 20)->nullable();
            $table->decimal('cost_idr', 15, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Drop in reverse dependency order
        Schema::dropIfExists('mng_ebd_add_processes');
        Schema::dropIfExists('mng_ebd_tooling_processes');

        // Drop self-referential FK before dropping table
        if (Schema::hasTable('mng_ebd_items')) {
            Schema::table('mng_ebd_items', function (Blueprint $table) {
                $table->dropForeign(['parent_id']);
            });
        }

        Schema::dropIfExists('mng_ebd_items');
        Schema::dropIfExists('mng_ebd_headers');
    }
};
