<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InquiryProductChat extends Model
{
    use HasFactory;

    protected $table = 'mng_inquiry_product_chats';

    protected $fillable = [
        'inquiry_product_id',
        'user_id',
        'message',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    public function inquiryProduct()
    {
        return $this->belongsTo(InquiryProduct::class, 'inquiry_product_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
