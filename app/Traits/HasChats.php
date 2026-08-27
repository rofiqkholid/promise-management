<?php

namespace App\Traits;

use App\Models\Chat;

trait HasChats
{
    /**
     * Get all chats for this model.
     */
    public function chats()
    {
        return $this->morphMany(Chat::class, 'chatable');
    }
}
