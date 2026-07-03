<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MngEbdItem extends Model
{
    use HasFactory;

    protected $table = 'mng_ebd_items';

    protected $fillable = [
        'ebd_header_id',
        'parent_id',
        'level_aktif',
        // Part identification
        'part_no',
        'part_name',
        'pcs_month',
        'sketch',
        // Part dimensions
        'qty_unit',
        'width',
        'length',
        'height',
        'weight',
        'status',
        'part_rank',
        // Material specification
        'mat_spec',
        'mat_thick',
        'mat_width',
        'mat_length',
        'mat_pcs_sheet',
        'mat_weight_pcs',
        'mat_yield_ratio',
        // Standard part
        'std_part_no',
        'std_qty',
        // Packing
        'packing_type',
        'pcs_packing',
        // Transport
        'part_vol_m2',
        'truck_vol_m2',
    ];

    protected $casts = [
        'level_aktif'     => 'integer',
        'pcs_month'       => 'integer',
        'qty_unit'        => 'integer',
        'width'           => 'decimal:2',
        'length'          => 'decimal:2',
        'height'          => 'decimal:2',
        'weight'          => 'decimal:3',
        'mat_thick'       => 'decimal:2',
        'mat_width'       => 'decimal:2',
        'mat_length'      => 'decimal:2',
        'mat_pcs_sheet'   => 'integer',
        'mat_weight_pcs'  => 'decimal:3',
        'mat_yield_ratio' => 'decimal:2',
        'std_qty'         => 'integer',
        'pcs_packing'     => 'integer',
        'part_vol_m2'     => 'decimal:4',
        'truck_vol_m2'    => 'decimal:4',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function header()
    {
        return $this->belongsTo(MngEbdHeader::class, 'ebd_header_id', 'id');
    }

    /**
     * Parent item in the BOM hierarchy.
     */
    public function parent()
    {
        return $this->belongsTo(MngEbdItem::class, 'parent_id', 'id');
    }

    /**
     * Direct children in the BOM hierarchy.
     */
    public function children()
    {
        return $this->hasMany(MngEbdItem::class, 'parent_id', 'id')
                    ->with(['children', 'toolingProcesses', 'addProcesses']);
    }

    public function toolingProcesses()
    {
        return $this->hasMany(MngEbdToolingProcess::class, 'ebd_item_id', 'id');
    }

    public function addProcesses()
    {
        return $this->hasMany(MngEbdAddProcess::class, 'ebd_item_id', 'id');
    }
}
