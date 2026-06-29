<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WoDocFormat extends Model
{
    use HasFactory;

    protected $table = 'mng_wo_doc_format';

    protected $fillable = [
        'document_no',
        'doc_department',
        'doc_publish_date',
        'page_hal',
        'is_current',
    ];

    protected $casts = [
        'doc_publish_date' => 'date',
        'is_current' => 'boolean',
    ];
}
