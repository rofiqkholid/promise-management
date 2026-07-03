<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasEncryptedId;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes, HasEncryptedId;

    protected $table = 'mng_work_orders';

    protected $fillable = [
        'inquiry_id',
        'wo_number',
        'revision_no',
        'revised_from_id',
        'is_latest',
        'header_id',
        'department_id',
        'priority',
        'subject',
        'request_types',
        'status',
        'remarks',
        'created_by',
        'first_sample_date',
        'due_date_plan',
        'due_dates_closed',
        'selected_approval_rule_ids',
        'released_at',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'revision_no' => 'integer',
        'request_types' => 'array',
        'first_sample_date' => 'date',
        'due_date_plan' => 'date',
        'due_dates_closed' => 'array',
        'selected_approval_rule_ids' => 'array',
        'released_at' => 'datetime',
    ];

    public function inquiry()
    {
        return $this->belongsTo(ProjectInquiry::class, 'inquiry_id', 'id');
    }

    public function ownerDepartment()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function docFormat()
    {
        return $this->belongsTo(WoDocFormat::class, 'header_id', 'id');
    }

    public function processes()
    {
        return $this->belongsToMany(WorkOrderProcess::class, 'mng_wo_process_details', 'work_order_id', 'process_id')
                    ->withPivot('id', 'remarks', 'assigned_departments');
    }

    public function products()
    {
        return $this->hasMany(WorkOrderProduct::class, 'work_order_id', 'id');
    }

    public function attachments()
    {
        return $this->hasMany(WorkOrderAttachment::class, 'work_order_id', 'id');
    }

    public function approvals()
    {
        return $this->hasMany(WorkOrderApproval::class, 'work_order_id', 'id');
    }

    public function revisedFrom()
    {
        return $this->belongsTo(WorkOrder::class, 'revised_from_id', 'id');
    }

    public function revisions()
    {
        return $this->hasMany(WorkOrder::class, 'revised_from_id', 'id');
    }

    /**
     * Accessor: $workOrder->work_order_no sebagai alias untuk wo_number.
     */
    public function getWorkOrderNoAttribute(): ?string
    {
        return $this->wo_number;
    }

    public function getSupportDepartmentsAttribute()
    {
        $deptIds = collect();
        foreach ($this->processes as $process) {
            $depts = json_decode($process->pivot->assigned_departments ?? '[]', true) ?: [];
            foreach ($depts as $dept) {
                $deptId = is_array($dept) ? ($dept['department_id'] ?? null) : $dept;
                if ($deptId && $deptId != $this->department_id) {
                    $deptIds->push((int)$deptId);
                }
            }
        }
        return Department::whereIn('id', $deptIds->unique())->get();
    }

    public function isApprover($user): bool
    {
        if ($this->status !== 'Pending Approval') {
            return false;
        }
        $pending = $this->approvals()->where('status', 'Pending')->get();
        foreach ($pending as $approval) {
            $rule = ApprovalConfig::activeFor('WO')
                ->where('approval_level', $approval->approval_level)
                ->where('department_id', $approval->department_id)
                ->first();
            if ($rule && $rule->canBeApprovedBy($user)) {
                return true;
            }
        }
        return false;
    }
}
