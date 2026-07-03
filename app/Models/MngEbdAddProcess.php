<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MngEbdAddProcess extends Model
{
    use HasFactory;

    protected $table = 'mng_ebd_add_processes';

    protected $fillable = [
        'ebd_item_id',
        'process_name',
        'qty',
        'unit',
        'cost_idr',
    ];

    protected $casts = [
        'qty'      => 'integer',
        'cost_idr' => 'decimal:2',
    ];

    public function ebdItem()
    {
        return $this->belongsTo(MngEbdItem::class, 'ebd_item_id', 'id');
    }
}
