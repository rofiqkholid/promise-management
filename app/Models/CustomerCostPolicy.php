<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerCostPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_customer_cost_policies';

    protected $fillable = [
        'customer_id',
        'admin_matrl_pct',
        'admin_mfg_pct',
        'oh_profit_pct',
        'min_std_margin_pct',
        'rate_source',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'admin_matrl_pct' => 'float',
        'admin_mfg_pct' => 'float',
        'oh_profit_pct' => 'float',
        'min_std_margin_pct' => 'float',
        'is_active' => 'boolean',
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
