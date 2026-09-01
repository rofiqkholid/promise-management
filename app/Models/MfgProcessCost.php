<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MfgProcessCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_mfg_process_costs';

    protected $fillable = [
        'customer_id',
        'category',
        'process_group',
        'process_name',
        'control_point',
        'uom',
        'rate_unit',
        'min_cost_rate',
        'std_cost_rate',
        'rate_source',
        'is_active',
    ];

    protected $casts = [
        'customer_id'   => 'integer',
        'min_cost_rate' => 'float',
        'std_cost_rate' => 'float',
        'is_active'     => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
