<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mng_approval_rules', function (Blueprint $table) {
            $table->id('rule_id');
            $table->string('document_type', 50)->default('SPK');
            $table->integer('approval_level'); // 1, 2, 3 ...
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('approver_user_id')->nullable(); // null = any user in dept
            $table->string('position_label', 100); // e.g. "Marketing GM", "Purchasing Manager"
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->onDelete('cascade');

            $table->foreign('approver_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mng_approval_rules');
    }
};
