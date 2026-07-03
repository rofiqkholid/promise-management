<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderApproval extends Model
{
    use HasFactory;

    protected $table = 'mng_wo_approvals';
    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'approval_level',
        'department_id',
        'approver_name',
        'approver_position',
        'status',
        'approved_at',
        'remarks',
        'due_date_closed',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'approval_level' => 'integer',
        'due_date_closed' => 'date',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}
