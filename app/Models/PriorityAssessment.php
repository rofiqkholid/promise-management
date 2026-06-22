<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriorityAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_priority_assessments';
    protected $primaryKey = 'assessment_id';

    protected $fillable = [
        'inquiry_product_id',
        'total_score',
        'ranking_id',
        'action',
        'action_override',
        'remarks',
        'assessed_by',
        'assessed_at',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(InquiryProduct::class, 'inquiry_product_id', 'inquiry_product_id');
    }

    public function ranking()
    {
        return $this->belongsTo(AssessmentRanking::class, 'ranking_id', 'ranking_id');
    }

    public function details()
    {
        return $this->hasMany(PriorityAssessmentDetail::class, 'assessment_id', 'assessment_id');
    }
}
