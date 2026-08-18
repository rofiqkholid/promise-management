<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialCost extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_material_costs';

    protected $fillable = [
        'customer_id',
        'material_spec',
        'material_type',
        'thickness',
        'price_per_kg',
        'scrap_price_per_kg',
        'rate_source',
        'valid_from',
        'is_active',
    ];

    protected $casts = [
        'thickness' => 'float',
        'price_per_kg' => 'float',
        'scrap_price_per_kg' => 'float',
        'valid_from' => 'date',
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
