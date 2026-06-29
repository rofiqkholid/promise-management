<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriorityAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_inq_assessments';

    protected $fillable = [
        'inquiry_product_id',
        'total_score',
        'ranking_id',
        'action',
        'remarks',
        'assessed_by',
        'assessed_at',
    ];

    protected $casts = [
        'assessed_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(InquiryProduct::class, 'inquiry_product_id', 'id');
    }

    public function ranking()
    {
        return $this->belongsTo(AssessmentRanking::class, 'ranking_id', 'id');
    }

    public function details()
    {
        return $this->hasMany(PriorityAssessmentDetail::class, 'assessment_id', 'id');
    }
}
