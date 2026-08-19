<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'email',
        'phone',
        'address',
    ];

    public function models()
    {
        return $this->hasMany(ProjectModel::class, 'customer_id');
    }

    public function inquiries()
    {
        return $this->hasMany(ProjectInquiry::class, 'customer_id');
    }
}
