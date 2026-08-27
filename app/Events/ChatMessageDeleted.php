<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $chatableType;
    public $chatableId;

    /**
     * Create a new event instance.
     */
    public function __construct($chatId, $chatableType, $chatableId)
    {
        $this->chatId = $chatId;
        $this->chatableType = $chatableType;
        $this->chatableId = $chatableId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatableType . '.' . $this->chatableId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'chatId' => $this->chatId,
            'chatableType' => $this->chatableType,
            'chatableId' => $this->chatableId,
        ];
    }
}
