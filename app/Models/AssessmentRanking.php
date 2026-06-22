<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentRanking extends Model
{
    use HasFactory;

    protected $table = 'mng_assessment_rankings';
    protected $primaryKey = 'ranking_id';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'rank_code',
        'min_score',
        'max_score',
        'priority_label',
        'recommendation',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
