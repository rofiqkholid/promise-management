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
        // 1. Tabel Header Quotation Supplier
        Schema::create('mng_tooling_quotations', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke EBD Header
            $table->unsignedBigInteger('ebd_header_id')->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();
            
            // Info Header Dokumen Penawaran
            $table->string('quotation_no', 100)->nullable();
            $table->string('revision', 20)->default('0');
            
            // Kurs / Exchange Rate (Contoh: CNY, USD, IDR)
            $table->string('currency_code', 10)->nullable(); // e.g. CNY, USD, IDR
            $table->decimal('exchange_rate', 15, 2)->default(1.00); // e.g., 2275.00
            
            // Total Ringkasan Biaya Penawaran Supplier
            $table->decimal('total_cost_foreign', 18, 2)->default(0);
            $table->decimal('total_cost_idr', 18, 2)->default(0);
            
            // File & Audit Trail
            $table->string('file_path')->nullable();
            $table->enum('status', ['DRAFT', 'IMPORTED', 'COMPARED', 'APPROVED', 'REJECTED'])->default('IMPORTED');
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->timestamp('imported_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('ebd_header_id')
                  ->references('id')
                  ->on('mng_ebd_headers')
                  ->onDelete('cascade');
        });

        // 2. Tabel Detail Penawaran Supplier per Baris / OP
        Schema::create('mng_tooling_quotation_details', function (Blueprint $table) {
            $table->id();
            
            // Relasi Header Quotation & Referensi EBD Item / Process (Mewakili Part & OP EBD)
            $table->unsignedBigInteger('tooling_quotation_id')->index();
            $table->unsignedBigInteger('ebd_item_id')->nullable()->index(); // Relasi ke Part (mng_ebd_items)
            $table->unsignedBigInteger('ebd_tooling_process_id')->nullable()->index(); // Relasi ke Process/OP EBD (mng_ebd_tooling_processes)
            
            // Info Proses (Menampung jika ada homeline dari supplier)
            $table->string('homeline', 50)->nullable(); // e.g. SAI / SUBCONT
            
            // Data Penawaran Supplier per OP/Tooling
            $table->string('supplier_status', 50)->nullable(); // NEW DIES / MODIF / COMMON
            $table->integer('op')->nullable(); // OP: 5, 10, 20, 30
            $table->string('tooling_process_name', 150)->nullable(); // BLANK PROG, FORM, CREST, PIE, CF NEW
            $table->string('tooling_type', 50)->nullable(); // DIES, JIG, CF
            
            // Spesifikasi & Dimensi dari Supplier
            $table->string('tonnage', 50)->nullable(); // e.g. 500, 400, "B"
            $table->decimal('die_height', 10, 2)->nullable();
            $table->string('die_category', 50)->nullable(); // Big, Medium, Small
            
            // Biaya Penawaran Supplier
            $table->decimal('cost_foreign', 18, 2)->default(0); // Biaya Valas
            $table->decimal('cost_idr', 18, 2)->default(0); // Biaya IDR
            
            $table->text('remarks')->nullable();
            
            $table->timestamps();

            // Foreign Key Constraint (Menggunakan no action untuk kompatibilitas SQL Server multiple cascade path)
            $table->foreign('tooling_quotation_id')
                  ->references('id')
                  ->on('mng_tooling_quotations')
                  ->onDelete('cascade');

            $table->foreign('ebd_item_id')
                  ->references('id')
                  ->on('mng_ebd_items')
                  ->onDelete('no action');

            $table->foreign('ebd_tooling_process_id')
                  ->references('id')
                  ->on('mng_ebd_tooling_processes')
                  ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mng_tooling_quotation_details');
        Schema::dropIfExists('mng_tooling_quotations');
    }
};
