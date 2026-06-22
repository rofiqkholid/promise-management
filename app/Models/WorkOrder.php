<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_work_orders';
    protected $primaryKey = 'work_order_id';

    protected $fillable = [
        'inquiry_id',
        'work_order_no',
        'revision_no',
        'revised_from_id',
        'is_latest',
        'department_id',
        'priority',
        'subject',
        'request_types',
        'status',
        'remarks',
        'created_by',
        'document_no',
        'doc_department',
        'publish_date',
        'page_hal',
    ];

    protected $casts = [
        'is_latest' => 'boolean',
        'revision_no' => 'integer',
        'request_types' => 'array',
        'publish_date' => 'date',
    ];

    public function inquiry()
    {
        return $this->belongsTo(ProjectInquiry::class, 'inquiry_id', 'inquiry_id');
    }

    public function ownerDepartment()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function supportDepartments()
    {
        return $this->belongsToMany(Department::class, 'mng_work_order_departments', 'work_order_id', 'department_id')
                    ->withPivot('remarks');
    }

    public function processes()
    {
        return $this->belongsToMany(WorkOrderProcess::class, 'mng_work_order_process_details', 'work_order_id', 'process_id')
                    ->withPivot('remarks');
    }

    public function products()
    {
        return $this->hasMany(WorkOrderProduct::class, 'work_order_id', 'work_order_id');
    }

    public function attachments()
    {
        return $this->hasMany(WorkOrderAttachment::class, 'work_order_id', 'work_order_id');
    }

    public function approvals()
    {
        return $this->hasMany(WorkOrderApproval::class, 'work_order_id', 'work_order_id');
    }

    public function revisedFrom()
    {
        return $this->belongsTo(WorkOrder::class, 'revised_from_id', 'work_order_id');
    }

    public function revisions()
    {
        return $this->hasMany(WorkOrder::class, 'revised_from_id', 'work_order_id');
    }
}
