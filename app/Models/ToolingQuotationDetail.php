<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolingQuotationDetail extends Model
{
    use HasFactory;

    protected $table = 'mng_tooling_quotation_details';

    protected $fillable = [
        'tooling_quotation_id',
        'ebd_item_id',
        'ebd_tooling_process_id',
        'homeline',
        'supplier_status',
        'op',
        'tooling_process_name',
        'tooling_type',
        'tonnage',
        'die_height',
        'die_category',
        'cost_foreign',
        'cost_idr',
        'remarks',
    ];

    protected $casts = [
        'op' => 'integer',
        'die_height' => 'decimal:2',
        'cost_foreign' => 'decimal:2',
        'cost_idr' => 'decimal:2',
    ];

    /**
     * Relasi ke Header Quotation
     */
    public function quotation()
    {
        return $this->belongsTo(ToolingQuotation::class, 'tooling_quotation_id');
    }

    /**
     * Relasi ke EBD Item (Part)
     */
    public function ebdItem()
    {
        return $this->belongsTo(MngEbdItem::class, 'ebd_item_id');
    }

    /**
     * Relasi ke EBD Tooling Process (OP EBD)
     */
    public function ebdToolingProcess()
    {
        return $this->belongsTo(MngEbdToolingProcess::class, 'ebd_tooling_process_id');
    }
}
