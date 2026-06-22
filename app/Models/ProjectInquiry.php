<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectInquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_project_inquiries';
    protected $primaryKey = 'inquiry_id';

    protected $fillable = [
        'inquiry_no',
        'customer_name',
        'project_name',
        'inquiry_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'inquiry_date' => 'date',
    ];

    public function products()
    {
        return $this->hasMany(InquiryProduct::class, 'inquiry_id', 'inquiry_id');
    }
}
