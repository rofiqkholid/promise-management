<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MfgProcessStpCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_mfg_process_stp_costs';

    protected $fillable = [
        'machine_type',
        'tonnage',
        'machine_category',
        'output_type',
        'output_qty',
        'stroke',
        'process_complexity',
        'complexity_alias',
        'min_cost_rate',
        'std_cost_rate',
        'rate_source',
        'is_active',
    ];

    protected $casts = [
        'tonnage' => 'integer',
        'output_qty' => 'integer',
        'stroke' => 'float',
        'min_cost_rate' => 'float',
        'std_cost_rate' => 'float',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
