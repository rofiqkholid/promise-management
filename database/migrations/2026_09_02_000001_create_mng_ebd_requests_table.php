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
        Schema::create('mng_ebd_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 100)->unique();
            $table->unsignedBigInteger('wo_id')->nullable()->index();
            $table->integer('customer_id')->nullable()->index();
            $table->integer('model_id')->nullable()->index();
            $table->unsignedBigInteger('ebd_header_id')->nullable()->index();
            $table->unsignedBigInteger('revised_ebd_id')->nullable()->index();
            $table->date('request_date');
            $table->string('request_type', 100);
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->string('status', 50)->default('Submitted')->index(); // Submitted, In Progress, Completed, Rejected
            $table->text('rejection_note')->nullable();
            $table->string('requested_by')->nullable();
            $table->string('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('wo_id')->references('id')->on('mng_work_orders')->onDelete('no action');
            $table->foreign('ebd_header_id')->references('id')->on('mng_ebd_headers')->onDelete('no action');
            $table->foreign('revised_ebd_id')->references('id')->on('mng_ebd_headers')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_ebd_requests');
    }
};
