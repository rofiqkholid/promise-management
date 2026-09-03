<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductQuotationDetail extends Model
{
    use HasFactory;

    protected $table = 'mng_product_quotation_details';

    protected $fillable = [
        'tooling_quotation_id',
        'ebd_item_id',
        'part_no',
        'part_name',
        'material_cost',
        'stamping_cost',
        'add_proc_cost',
        'mfg_process_cost',
        'cogm',
        'cogs',
        'remarks',
    ];

    protected $casts = [
        'material_cost' => 'decimal:2',
        'stamping_cost' => 'decimal:2',
        'add_proc_cost' => 'decimal:2',
        'mfg_process_cost' => 'decimal:2',
        'cogm' => 'decimal:2',
        'cogs' => 'decimal:2',
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
}
