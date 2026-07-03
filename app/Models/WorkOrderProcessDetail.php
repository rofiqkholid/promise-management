<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderProcessDetail extends Model
{
    use HasFactory;

    protected $table = 'mng_wo_process_details';

    // Disable timestamps as pivot tables usually don't have them unless specified
    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'process_id',
        'assigned_departments',
        'remarks',
    ];

    protected $casts = [
        'assigned_departments' => 'array',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id');
    }

    public function process()
    {
        return $this->belongsTo(WorkOrderProcess::class, 'process_id', 'id');
    }
}
