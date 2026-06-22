<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriorityAssessmentDetail extends Model
{
    use HasFactory;

    protected $table = 'mng_priority_assessment_details';
    protected $primaryKey = 'detail_id';
    public $timestamps = false;

    protected $fillable = [
        'assessment_id',
        'category_id',
        'option_id',
        'score_snapshot',
        'remarks',
    ];

    public function assessment()
    {
        return $this->belongsTo(PriorityAssessment::class, 'assessment_id', 'assessment_id');
    }

    public function category()
    {
        return $this->belongsTo(ScoreCategory::class, 'category_id', 'category_id');
    }

    public function option()
    {
        return $this->belongsTo(ScoreOption::class, 'option_id', 'option_id');
    }
}
