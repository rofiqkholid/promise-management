<?php

namespace App\Events;

use App\Models\InquiryProductChat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InquiryProductChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;
    public $messageData;

    /**
     * Create a new event instance.
     */
    public function __construct(InquiryProductChat $chat)
    {
        $this->chat = $chat;
        
        // Prepare optimized payload for front-end rendering
        $this->messageData = [
            'id' => $chat->id,
            'inquiry_product_id' => $chat->inquiry_product_id,
            'message' => $chat->message,
            'user_id' => $chat->user_id,
            'user_name' => $chat->user->name ?? 'System',
            'created_at' => $chat->created_at->format('Y-m-d H:i:s'),
            'time_label' => $chat->created_at->format('d M Y, H:i'),
            'file_name' => $chat->file_name,
            'file_path' => $chat->file_path,
            'file_type' => $chat->file_type,
            'file_size' => $chat->file_size,
            'download_url' => $chat->file_path ? route('management.inquiry-product.chats.download', $chat->id) : null,
            'file_url' => $chat->file_path ? route('management.inquiry-product.chats.show-file', $chat->id) : null,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('inquiry-product-chat.' . $this->chat->inquiry_product_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->messageData;
    }
}
