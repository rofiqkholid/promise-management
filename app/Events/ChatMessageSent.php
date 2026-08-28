<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;
    public $messageData;

    /**
     * Create a new event instance.
     */
    public function __construct(Chat $chat, ?array $customPayload = null)
    {
        $this->chat = $chat;

        if ($customPayload) {
            $this->messageData = $customPayload;
            return;
        }

        $replyData = null;
        if ($chat->reply_to_id && $chat->replyTo) {
            $replyData = [
                'id' => $chat->replyTo->id,
                'user_name' => $chat->replyTo->user->name ?? 'User',
                'message' => $chat->replyTo->message,
                'file_name' => $chat->replyTo->file_name,
                'file_type' => $chat->replyTo->file_type,
            ];
        }

        $attachments = [];
        if ($chat->file_path) {
            if (str_starts_with($chat->file_path, '[') && str_ends_with($chat->file_path, ']')) {
                $decoded = json_decode($chat->file_path, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $idx => $att) {
                        $filePath = $att['file_path'] ?? '';
                        $fHash = substr(md5($filePath ?: (string)$idx), 0, 12);
                        $attachments[] = [
                            'index' => $idx,
                            'f' => $fHash,
                            'file_name' => $att['file_name'] ?? 'File',
                            'file_path' => $filePath,
                            'file_type' => $att['file_type'] ?? '',
                            'file_size' => $att['file_size'] ?? 0,
                            'file_url' => route('management.chats.show-file', [$chat->id, 'f' => $fHash]),
                            'download_url' => route('management.chats.download', [$chat->id, 'f' => $fHash]),
                        ];
                    }
                }
            } else {
                $fHash = substr(md5($chat->file_path ?: '0'), 0, 12);
                $attachments[] = [
                    'index' => 0,
                    'f' => $fHash,
                    'file_name' => $chat->file_name,
                    'file_path' => $chat->file_path,
                    'file_type' => $chat->file_type,
                    'file_size' => $chat->file_size,
                    'file_url' => route('management.chats.show-file', $chat->id),
                    'download_url' => route('management.chats.download', $chat->id),
                ];
            }
        }

        // Standardized polymorphic payload
        $this->messageData = [
            'id' => $chat->id,
            'chatable_type' => $chat->chatable_type,
            'chatable_id' => $chat->chatable_id,
            'user_id' => $chat->user_id,
            'user_name' => $chat->user->name ?? 'User',
            'reply_to' => $replyData,
            'tagged_user_ids' => $chat->tagged_user_ids ?? [],
            'tagged_items' => $chat->tagged_items ?? [],
            'message' => $chat->message,
            'created_at' => $chat->created_at ? $chat->created_at->format('Y-m-d H:i:s') : null,
            'time_label' => $chat->created_at ? $chat->created_at->format('d M Y, H:i') : '',
            'attachments' => $attachments,
            'file_name' => $chat->file_name,
            'file_path' => $chat->file_path,
            'file_type' => $chat->file_type,
            'file_size' => $chat->file_size,
            'download_url' => $chat->file_path ? route('management.chats.download', $chat->id) : null,
            'file_url' => $chat->file_path ? route('management.chats.show-file', $chat->id) : null,
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
            new PrivateChannel('chat.' . $this->chat->chatable_type . '.' . $this->chat->chatable_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ChatMessageSent';
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
