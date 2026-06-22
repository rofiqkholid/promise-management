<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderProcess extends Model
{
    use HasFactory;

    protected $table = 'mng_work_order_processes';
    protected $primaryKey = 'process_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'process_code',
        'process_name',
        'owner_department_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ownerDepartment()
    {
        return $this->belongsTo(Department::class, 'owner_department_id', 'id');
    }
}
