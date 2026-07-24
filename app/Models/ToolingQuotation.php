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
        'quotation_no',
        'revision',
        'currency_name',
        'exchange_rate',
        'total_cost_foreign',
        'total_cost_idr',
        'file_path',
        'status',
        'imported_by',
        'imported_at',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:2',
        'total_cost_foreign' => 'decimal:2',
        'total_cost_idr' => 'decimal:2',
        'imported_at' => 'datetime',
    ];

    /**
     * Relasi ke EBD Header Target
     */
    public function ebdHeader()
    {
        return $this->belongsTo(MngEbdHeader::class, 'ebd_header_id');
    }

    /**
     * Relasi ke Detail Penawaran Supplier
     */
    public function details()
    {
        return $this->hasMany(ToolingQuotationDetail::class, 'tooling_quotation_id');
    }

    /**
     * Relasi ke User yang meng-import
     */
    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
