<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InqReviewedProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_inq_reviewed_products';

    protected $fillable = [
        'reviewer',
    ];
}
