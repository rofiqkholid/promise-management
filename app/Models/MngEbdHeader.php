<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MngEbdHeader extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_ebd_headers';

    protected $fillable = [
        'wo_id',
        'customer_id',
        'model_id',
        'date',
        'revision',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function workOrder()
    {
        return $this->belongsTo(WorkOrder::class, 'wo_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function projectModel()
    {
        return $this->belongsTo(ProjectModel::class, 'model_id', 'id');
    }

    /**
     * All EBD items (BOM components) belonging to this header.
     */
    public function items()
    {
        return $this->hasMany(MngEbdItem::class, 'ebd_header_id', 'id');
    }

    /**
     * Only root-level items (Level 1, no parent).
     */
    public function rootItems()
    {
        return $this->hasMany(MngEbdItem::class, 'ebd_header_id', 'id')
                    ->whereNull('parent_id')
                    ->with('children');
    }
}
