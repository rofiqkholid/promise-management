<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasEncryptedId;

class InquiryProduct extends Model
{
    use HasFactory, SoftDeletes, HasEncryptedId;

    protected $table = 'mng_inquiry_products';

    protected $appends = ['model_name'];

    protected $fillable = [
        'inquiry_id',
        'customer_part_no',
        'customer_part_name',
        'part_category',
        'destination',
        'sop_date',
        'eol_date',
        'model_life',
        'annual_volume',
        'has_2d_data',
        'has_3d_data',
        'has_tech_doc',
        'variant',
        'remarks',
    ];

    protected $casts = [
        'sop_date' => 'date',
        'eol_date' => 'date',
        'has_2d_data' => 'boolean',
        'has_3d_data' => 'boolean',
        'has_tech_doc' => 'boolean',
    ];

    public function inquiry()
    {
        return $this->belongsTo(ProjectInquiry::class, 'inquiry_id', 'id');
    }

    public function getModelNameAttribute(): ?string
    {
        return $this->inquiry->model_name ?? null;
    }

    public function assessment()
    {
        return $this->hasOne(PriorityAssessment::class, 'inquiry_product_id', 'id');
    }

    public function workOrderProducts()
    {
        return $this->hasMany(WorkOrderProduct::class, 'inquiry_product_id', 'id');
    }

    public function chats()
    {
        return $this->hasMany(InquiryProductChat::class, 'inquiry_product_id', 'id');
    }
}

