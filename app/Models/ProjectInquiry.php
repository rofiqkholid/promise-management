<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectInquiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mng_inquiries';

    protected $fillable = [
        'inquiry_no',
        'customer_id',
        'project_name',
        'model_id',
        'inquiry_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'inquiry_date' => 'date',
    ];

    public function products()
    {
        return $this->hasMany(InquiryProduct::class, 'inquiry_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function projectModel()
    {
        return $this->belongsTo(ProjectModel::class, 'model_id');
    }

    /**
     * Accessor: nama model dari relasi.
     */
    public function getModelNameAttribute(): ?string
    {
        return $this->projectModel->name ?? null;
    }
}
