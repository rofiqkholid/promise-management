<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreCategory extends Model
{
    use HasFactory;

    protected $table = 'mng_inq_score_categories';

    protected $fillable = [
        'category_code',
        'category_name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function options()
    {
        return $this->hasMany(ScoreOption::class, 'category_id', 'id');
    }
}
