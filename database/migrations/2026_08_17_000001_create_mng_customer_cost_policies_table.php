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
        Schema::create('mng_customer_cost_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable()->index(); // Null = Global / Standard Eng, Terisi = Khusus Customer (Sales)

            // 1. Admin Rates (Persentase Biaya Tambahan)
            $table->decimal('admin_matrl_pct', 5, 2)->default(2.00); // Admin Material % (Eng = 2%, Sales = Sesuai Customer)
            $table->decimal('admin_mfg_pct', 5, 2)->default(4.00);   // Admin Mfg % (Eng = 4%, Sales = Sesuai Customer)
            
            // 2. Overhead & Profit
            $table->decimal('oh_profit_pct', 5, 2)->default(0.00);   // O/H + Profit % (Eng = 0%, Sales = Sales Strategy)
            
            // 3. Batas Ambang Standar Minimum Margin
            $table->decimal('min_std_margin_pct', 5, 2)->default(12.00); // Target Minimum Margin % (Standar = Min. 12%)

            // 4. Metadata
            $table->string('rate_source')->default('Sales'); // Engineering, Sales
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'rate_source', 'is_active'], 'cust_cost_policy_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_customer_cost_policies');
    }
};
