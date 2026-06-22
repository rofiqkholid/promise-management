<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderAttachment extends Model
{
    use HasFactory;

    protected $table = 'mng_work_order_attachments';
    protected $primaryKey = 'attachment_id';
    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'file_name',
        'file_path',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'work_order_id', 'work_order_id');
    }
}
