<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MngCfgTemplate extends Model
{
    protected $table = 'mng_cfg_templates';

    protected $fillable = [
        'template_type',
        'direction',
        'customer_id',
        'template_name',
        'revision',
        'file_path',
        'mapping_config',
        'is_active',
    ];

    protected $casts = [
        'mapping_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
