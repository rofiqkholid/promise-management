<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MngEbdToolingProcess extends Model
{
    use HasFactory;

    protected $table = 'mng_ebd_tooling_processes';

    protected $fillable = [
        'ebd_item_id',
        'tool_rank',
        'category',
        'op',
        'process_name',
        'prod_homeline',
        'tonnage',
        'die_height',
        'output',
        'output_type',
        'qty',
        'price_idr',
        'tooling_status',
        'information',
    ];

    protected $casts = [
        'op'         => 'integer',
        'tonnage'    => 'integer',
        'die_height' => 'decimal:2',
        'output'     => 'integer',
        'qty'        => 'integer',
        'price_idr'  => 'decimal:2',
    ];

    public function ebdItem()
    {
        return $this->belongsTo(MngEbdItem::class, 'ebd_item_id', 'id');
    }
}
