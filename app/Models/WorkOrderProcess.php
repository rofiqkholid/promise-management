<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkOrderProcess extends Model
{
    use HasFactory;

    protected $table = 'mng_wo_processes';

    protected $fillable = [
        'process_code',
        'process_name',
        'default_assigned_departments',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getDefaultAssignedDepartments()
    {
        $data = json_decode($this->default_assigned_departments ?? '[]', true) ?: [];
        $ids = array_map(function($d) {
            return is_array($d) ? ($d['department_id'] ?? $d) : $d;
        }, $data);
        if (empty($ids)) {
            return collect();
        }
        return Department::whereIn('id', $ids)->get();
    }
}
