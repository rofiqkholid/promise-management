<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_wo_products';

    protected $fillable = [
        'work_order_id',
        'inquiry_product_id',
        'customer_name',
        'model_name',
        'customer_part_no',
        'customer_part_name',
        'eo',
        'class_id',
        'uom',
        'destination',
        'sop_date',
        'eol_date',
        'model_life',
        'annual_volume',
        'variant',
        'has_2d_data',
        'has_3d_data',
        'has_tech_doc',
        'remarks',
        'ebd_item_id',
        // SPK2 Fields
        'ebd_add_process_id',
        'add_process_name',
        'add_process_qty',
        'add_process_unit',
        'mat_spec',
        'mat_size',
        'mat_weight_pcs',
    ];

    protected $casts = [
        'sop_date' => 'date',
        'eol_date' => 'date',
        'has_2d_data' => 'boolean',
        'has_3d_data' => 'boolean',
        'has_tech_doc' => 'boolean',
        'add_process_qty' => 'integer',
        'mat_weight_pcs' => 'decimal:3',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id');
    }

    public function inquiryProduct()
    {
        return $this->belongsTo(InquiryProduct::class, 'inquiry_product_id', 'id');
    }

    public function ebdItem()
    {
        return $this->belongsTo(MngEbdItem::class, 'ebd_item_id', 'id');
    }

    public function ebdAddProcess()
    {
        return $this->belongsTo(MngEbdAddProcess::class, 'ebd_add_process_id', 'id');
    }
}
