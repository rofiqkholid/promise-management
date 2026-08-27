<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $table = 'mng_chats';

    protected $fillable = [
        'chatable_type',
        'chatable_id',
        'user_id',
        'reply_to_id',
        'message',
        'tagged_user_ids',
        'tagged_items',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    protected $casts = [
        'tagged_user_ids' => 'array',
        'tagged_items' => 'array',
        'file_size' => 'integer',
    ];

    /**
     * Morph relation to the parent model (WorkOrder, InquiryProduct, etc.)
     */
    public function chatable()
    {
        return $this->morphTo();
    }

    /**
     * Sender of the chat message.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Message being replied to.
     */
    public function replyTo()
    {
        return $this->belongsTo(Chat::class, 'reply_to_id', 'id')->with('user');
    }

    /**
     * Replies for this message.
     */
    public function replies()
    {
        return $this->hasMany(Chat::class, 'reply_to_id', 'id');
    }
}
