<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_approval_config', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 50)->default('WO');
            $table->integer('approval_level');
            $table->integer('department_id');
            $table->text('approver_user_ids')->nullable();
            $table->string('position_label', 100);
            $table->string('action_label', 50)->default('Checked');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');
        });

        Schema::create('mng_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->text('description')->nullable();
            $table->string('color', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('mng_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('module_name', 100);
            $table->string('action', 50);
            $table->integer('record_id');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_audit_logs');
        Schema::dropIfExists('mng_calendar_events');
        Schema::dropIfExists('mng_approval_config');
    }
};
