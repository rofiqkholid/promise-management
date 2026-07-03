<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_wo_doc_format', function (Blueprint $table) {
            $table->id();
            $table->string('document_no', 100);
            $table->string('doc_department', 100);
            $table->date('doc_publish_date')->nullable();
            $table->string('page_hal', 50);
            $table->integer('revision_no')->default(0);
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        // Seed default QEMS template header
        DB::table('mng_wo_doc_format')->insert([
            'document_no' => 'FO-13-02',
            'doc_department' => 'Sales',
            'doc_publish_date' => '2024-01-01',
            'page_hal' => '1',
            'revision_no' => 1,
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('mng_work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')
                ->constrained('mng_inquiries')
                ->onDelete('cascade');
            $table->string('wo_number', 100);
            $table->integer('revision_no')->default(0);
            $table->unsignedBigInteger('revised_from_id')->nullable();
            $table->boolean('is_latest')->default(true);
            $table->foreignId('header_id')
                ->constrained('mng_wo_doc_format')
                ->onDelete('cascade');
            $table->integer('department_id'); // Owner department from departments table
            $table->string('priority', 50)->nullable();
            $table->string('subject', 255)->nullable();
            $table->text('request_types')->nullable();
            $table->string('status', 50);
            $table->text('remarks')->nullable();
            $table->string('created_by', 100);
            $table->datetime('released_at')->nullable();
            $table->date('first_sample_date')->nullable();
            $table->date('due_date_plan')->nullable();
            $table->text('due_dates_closed')->nullable();
            $table->text('selected_approval_rule_ids')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['wo_number', 'revision_no']);

            $table->foreign('revised_from_id')
                ->references('id')
                ->on('mng_work_orders')
                ->onDelete('no action');

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });

        Schema::create('mng_wo_processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_code', 100)->unique();
            $table->string('process_name', 255);
            $table->text('default_assigned_departments')->nullable(); // JSON Array of Department IDs
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mng_wo_process_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')
                ->constrained('mng_work_orders')
                ->onDelete('cascade');
            $table->foreignId('process_id')
                ->constrained('mng_wo_processes')
                ->onDelete('cascade');
            $table->text('assigned_departments')->nullable(); // JSON Array of Department IDs
            $table->text('remarks')->nullable();
        });

        Schema::create('mng_wo_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')
                ->constrained('mng_work_orders')
                ->onDelete('cascade');
            $table->foreignId('inquiry_product_id')
                ->constrained('mng_inquiry_products')
                ->onDelete('no action');

            $table->string('customer_name', 255)->nullable();
            $table->string('model_name', 255)->nullable();
            $table->string('variant', 100)->nullable();
            $table->string('customer_part_no', 100);
            $table->string('customer_part_name', 255);
            $table->string('eo', 100)->nullable();
            $table->string('class_id', 100)->nullable();
            $table->string('uom', 50)->nullable();
            $table->string('destination', 100)->nullable();
            $table->date('sop_date')->nullable();
            $table->date('eol_date')->nullable();
            $table->integer('model_life')->nullable();
            $table->integer('annual_volume')->nullable();
            $table->boolean('has_2d_data')->default(false);
            $table->boolean('has_3d_data')->default(false);
            $table->boolean('has_tech_doc')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mng_wo_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')
                ->constrained('mng_work_orders')
                ->onDelete('cascade');
            $table->integer('approval_level');
            $table->integer('department_id');
            $table->string('approver_name', 255)->nullable();
            $table->string('approver_position', 255)->nullable();
            $table->string('status', 50);
            $table->datetime('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->date('due_date_closed')->nullable();
            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('no action');
        });
    }

    public function down(): void
    {
        Schema::table('mng_work_orders', function (Blueprint $table) {
            // Drop self-referential foreign key first to avoid SQL Server block
            $table->dropForeign(['revised_from_id']);
        });

        Schema::dropIfExists('mng_wo_approvals');
        Schema::dropIfExists('mng_wo_products');
        Schema::dropIfExists('mng_wo_process_details');
        Schema::dropIfExists('mng_wo_processes');
        Schema::dropIfExists('mng_work_orders');
        Schema::dropIfExists('mng_wo_doc_format');
    }
};
