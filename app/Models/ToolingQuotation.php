<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ToolingQuotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_tooling_quotations';

    protected $fillable = [
        'ebd_header_id',
        'supplier_id',
        'customer_id',
        'source_type',
        'quotation_type',
        'quotation_no',
        'revision',
        'currency_code',
        'exchange_rate',
        'admin_matrl_pct',
        'admin_mfg_pct',
        'oh_profit_pct',
        'total_material_cost',
        'total_mfg_cost',
        'total_product_cogs',
        'total_cost_foreign',
        'total_cost_idr',
        'file_path',
        'status',
        'imported_by',
        'imported_at',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:2',
        'admin_matrl_pct' => 'decimal:2',
        'admin_mfg_pct' => 'decimal:2',
        'oh_profit_pct' => 'decimal:2',
        'total_material_cost' => 'decimal:2',
        'total_mfg_cost' => 'decimal:2',
        'total_product_cogs' => 'decimal:2',
        'total_cost_foreign' => 'decimal:2',
        'total_cost_idr' => 'decimal:2',
        'imported_at' => 'datetime',
    ];

    /**
     * Accessor untuk nama/label mata uang lengkap dari CurrencyHelper
     */
    public function getCurrencyLabelAttribute(): string
    {
        return \App\Helpers\CurrencyHelper::formatLabel($this->currency_code) ?: ($this->currency_code ?? 'IDR');
    }

    /**
     * Accessor untuk simbol mata uang dari CurrencyHelper
     */
    public function getCurrencySymbolAttribute(): string
    {
        return \App\Helpers\CurrencyHelper::getSymbol($this->currency_code) ?: 'Rp';
    }

    /**
     * Relasi ke EBD Header Target
     */
    public function ebdHeader()
    {
        return $this->belongsTo(MngEbdHeader::class, 'ebd_header_id');
    }

    /**
     * Relasi ke Detail Penawaran Supplier (Tooling)
     */
    public function details()
    {
        return $this->hasMany(ToolingQuotationDetail::class, 'tooling_quotation_id');
    }

    /**
     * Relasi ke Detail Penawaran Produk (Part Breakdown)
     */
    public function productDetails()
    {
        return $this->hasMany(ProductQuotationDetail::class, 'tooling_quotation_id');
    }

    /**
     * Relasi ke Master Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }

    /**
     * Relasi ke Master Customer
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Accessor untuk supplier_name / display name
     */
    public function getSupplierNameAttribute()
    {
        if ($this->source_type === 'sales') {
            return 'Sales Revision';
        }
        if ($this->source_type === 'customer' || $this->customer_id) {
            return $this->customer ? ($this->customer->code ?? $this->customer->name) : 'Customer Target';
        }
        return $this->supplier ? $this->supplier->name : '—';
    }

    /**
     * Relasi ke User yang meng-import
     */
    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
