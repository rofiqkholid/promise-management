<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreOption extends Model
{
    use HasFactory;

    protected $table = 'mng_score_options';
    protected $primaryKey = 'option_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'category_id',
        'option_name',
        'score_value',
        'description',
        'sort_order',
    ];

    public function category()
    {
        return $this->belongsTo(ScoreCategory::class, 'category_id', 'category_id');
    }
}
