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

    protected static $departmentsCache = null;

    public function getSupportDepartmentsAttribute()
    {
        if (self::$departmentsCache === null) {
            self::$departmentsCache = Department::all()->keyBy('id');
        }

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
        return self::$departmentsCache->only($deptIds->unique()->toArray())->values();
    }

    public function getDepartmentProgress()
    {
        $progress = [];
        $productsCount = $this->products->count();

        $departments = collect();
        if ($this->ownerDepartment) {
            $departments->push($this->ownerDepartment);
        }
        foreach ($this->supportDepartments as $sd) {
            $departments->push($sd);
        }
        $departments = $departments->unique('id');

        foreach ($departments as $dept) {
            $progress[$dept->id] = [
                'code' => $dept->code,
                'completed' => 0,
                'total' => 0
            ];
        }

        foreach ($this->processes as $process) {
            $deptsData = json_decode($process->pivot->assigned_departments ?? '[]', true) ?: [];
            foreach ($deptsData as $d) {
                $deptId = is_array($d) ? ($d['department_id'] ?? null) : $d;
                $checkedProductIds = is_array($d) ? ($d['checked_product_ids'] ?? []) : [];
                if ($deptId && isset($progress[$deptId])) {
                    $progress[$deptId]['total'] += $productsCount;
                    $progress[$deptId]['completed'] += count($checkedProductIds);
                }
            }
        }

        foreach ($progress as $deptId => &$data) {
            $data['percent'] = $data['total'] > 0 ? round(($data['completed'] / $data['total']) * 100) : 0;
        }

        return $progress;
    }

    public function getTargetDepartmentsFullAttribute(): string
    {
        $deptNames = collect();
        if ($this->ownerDepartment) {
            $deptNames->push($this->ownerDepartment->name);
        }
        foreach ($this->supportDepartments as $sd) {
            if ($sd->name) {
                $deptNames->push($sd->name);
            }
        }
        return $deptNames->unique()->filter()->implode(' / ') ?: '—';
    }

    public function getTargetDepartmentsAttribute(): string
    {
        $deptCodes = collect();
        if ($this->ownerDepartment && $this->ownerDepartment->code) {
            $deptCodes->push($this->ownerDepartment->code);
        }
        foreach ($this->supportDepartments as $sd) {
            if ($sd->code) {
                $deptCodes->push($sd->code);
            }
        }
        return $deptCodes->unique()->filter()->implode(' / ') ?: '—';
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
