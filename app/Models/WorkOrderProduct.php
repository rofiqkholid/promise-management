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
    ];

    protected $casts = [
        'sop_date' => 'date',
        'eol_date' => 'date',
        'has_2d_data' => 'boolean',
        'has_3d_data' => 'boolean',
        'has_tech_doc' => 'boolean',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id');
    }

    public function inquiryProduct()
    {
        return $this->belongsTo(InquiryProduct::class, 'inquiry_product_id', 'id');
    }
}
