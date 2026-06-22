<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrderPart extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_work_order_parts';
    protected $primaryKey = 'work_order_part_id';

    protected $fillable = [
        'work_order_product_id',
        'eo',
        'part_no',
        'part_name',
        'class_id',
        'uom',
        'remarks',
    ];

    public function product()
    {
        return $this->belongsTo(WorkOrderProduct::class, 'work_order_product_id', 'work_order_product_id');
    }
}
