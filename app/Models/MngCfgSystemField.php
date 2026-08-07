<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MngCfgSystemField extends Model
{
    protected $table = 'mng_cfg_system_fields';

    protected $fillable = [
        'field_key',
        'label',
        'group',
        'data_type',
        'target_table',
        'target_column',
        'is_required',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];
}
