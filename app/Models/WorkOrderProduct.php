<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_work_order_products';
    protected $primaryKey = 'work_order_product_id';

    protected $fillable = [
        'work_order_id',
        'inquiry_product_id',
        'customer_name',
        'model_name',
        'customer_part_no',
        'customer_part_name',
        'destination',
        'sop_date',
        'eol_date',
        'model_life',
        'annual_volume',
        'first_sample_date',
        'due_date_approval',
        'due_date_closed',
        'remarks',
    ];

    protected $casts = [
        'sop_date' => 'date',
        'eol_date' => 'date',
        'first_sample_date' => 'date',
        'due_date_approval' => 'date',
        'due_date_closed' => 'date',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'work_order_id');
    }

    public function inquiryProduct()
    {
        return $this->belongsTo(InquiryProduct::class, 'inquiry_product_id', 'inquiry_product_id');
    }

    public function parts()
    {
        return $this->hasMany(WorkOrderPart::class, 'work_order_product_id', 'work_order_product_id');
    }
}
