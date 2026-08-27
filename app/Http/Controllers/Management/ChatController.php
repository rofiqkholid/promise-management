<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\InquiryProduct;
use App\Events\ChatMessageSent;
use App\Events\ChatMessageDeleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Fetch chat history for any chatable module.
     */
    public function index(Request $request, $type, $id)
    {
        $query = Chat::with(['user', 'replyTo.user'])
            ->where('chatable_type', $type)
            ->where('chatable_id', $id);

        if ($request->has('q') && filled($request->input('q'))) {
            $searchTerm = '%' . trim($request->input('q')) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('message', 'like', $searchTerm)
                  ->orWhere('file_name', 'like', $searchTerm)
                  ->orWhereHas('user', function ($uq) use ($searchTerm) {
                      $uq->where('name', 'like', $searchTerm);
                  });
            });
            $messages = $query->orderBy('id', 'desc')
                ->take(50)
                ->get()
                ->reverse()
                ->values();
        } elseif ($request->has('target_id') && $request->input('target_id')) {
            $targetId = (int) $request->input('target_id');
            if ($request->has('before_id') && $request->input('before_id')) {
                $query->where('id', '<', $request->input('before_id'));
            }
            $query->where('id', '>=', $targetId);
            $messages = $query->orderBy('id', 'desc')
                ->take(50)
                ->get()
                ->reverse()
                ->values();
        } else {
            if ($request->has('before_id') && $request->input('before_id')) {
                $query->where('id', '<', $request->input('before_id'));
            }

            // Load 20 chats at a time for fast initial loading
            $limit = (int) $request->input('limit', 20);
            $messages = $query->orderBy('id', 'desc')
                ->take($limit)
                ->get()
                ->reverse()
                ->values();
        }

        $hasMore = false;
        if ($messages->isNotEmpty() && !$request->has('q')) {
            $oldestId = $messages->first()->id;
            $hasMore = Chat::where('chatable_type', $type)
                ->where('chatable_id', $id)
                ->where('id', '<', $oldestId)
                ->exists();
        }

        $formatted = $messages->map(function ($chat) {
            return $this->formatChatPayload($chat);
        });

        return response()->json([
            'success' => true,
            'messages' => $formatted,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * Store a new chat message for any module.
     */
    public function store(Request $request, $type, $id)
    {
        $request->validate([
            'message' => 'nullable|string',
            'reply_to_id' => 'nullable|integer',
            'tagged_user_ids' => 'nullable',
            'tagged_items' => 'nullable',
            'file' => 'nullable|file|max:10240',
            'files.*' => 'nullable|file|max:10240',
        ]);

        $hasFiles = $request->hasFile('files') || $request->hasFile('file');
        if (!$request->input('message') && !$hasFiles) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send an empty message.'
            ], 422);
        }

        // Process tagged users & items if sent as JSON string
        $taggedUserIds = $request->input('tagged_user_ids');
        if (is_string($taggedUserIds)) {
            $taggedUserIds = json_decode($taggedUserIds, true);
        }

        $taggedItems = $request->input('tagged_items');
        if (is_string($taggedItems)) {
            $taggedItems = json_decode($taggedItems, true);
        }

        $files = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
        } elseif ($request->hasFile('file')) {
            $files = [$request->file('file')];
        }

        $fileData = [];
        $attachmentsList = [];

        if (!empty($files)) {
            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $mimeType = $file->getClientMimeType();
                $size = $file->getSize();
                $path = $file->store("public/chats/{$type}/{$id}");

                $attachmentsList[] = [
                    'file_path' => $path,
                    'file_name' => $originalName,
                    'file_type' => $mimeType,
                    'file_size' => $size,
                ];
            }

            if (count($attachmentsList) === 1) {
                $fileData = $attachmentsList[0];
            } else {
                $fileData = [
                    'file_path' => json_encode($attachmentsList),
                    'file_name' => count($attachmentsList) . ' files attached',
                    'file_type' => 'multipart/mixed',
                    'file_size' => array_sum(array_column($attachmentsList, 'file_size')),
                ];
            }
        }

        $chat = Chat::create(array_merge([
            'chatable_type' => $type,
            'chatable_id' => $id,
            'user_id' => Auth::user()->id,
            'reply_to_id' => $request->input('reply_to_id') ?: null,
            'message' => $request->input('message'),
            'tagged_user_ids' => $taggedUserIds ?: null,
            'tagged_items' => $taggedItems ?: null,
        ], $fileData));

        // Load reply relation for payload
        $chat->load(['user', 'replyTo.user']);

        // Broadcast to others in the channel (gracefully catch if Reverb is temporarily offline)
        $payload = $this->formatChatPayload($chat);
        try {
            broadcast(new ChatMessageSent($chat, $payload))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Chat Reverb broadcast error on send: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message_data' => $payload,
        ]);
    }

    /**
     * Delete a single attachment from a message, matched by file_path or f hash (stable identifier).
     */
    public function destroyAttachment(Request $request, $chatId, $attachmentIndex)
    {
        $chat = Chat::with('user')->findOrFail($chatId);

        $currentUser = Auth::user();
        if (!$currentUser) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        $isAuthor = ((int) $chat->user_id === (int) $currentUser->id);
        if (!$isAuthor && $chat->user) {
            $isAuthor = ($chat->user->nik === $currentUser->nik);
        }

        if (!$isAuthor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to delete this attachment.'], 403);
        }

        $filePath = $chat->file_path;

        if (!$filePath) {
            return response()->json(['success' => false, 'message' => 'No attachment found.'], 404);
        }

        $targetFilePath = $request->input('file_path') ?: $request->query('file_path');
        $targetFHash = $request->input('f') ?: $request->query('f');

        $trimmed = trim($filePath);
        if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
            $decoded = json_decode($trimmed, true);
            if (!is_array($decoded)) {
                return response()->json(['success' => false, 'message' => 'Invalid attachment data.'], 422);
            }

            $foundKey = null;
            // 1. Match by exact file_path if provided
            if ($targetFilePath) {
                foreach ($decoded as $k => $att) {
                    if (($att['file_path'] ?? '') === $targetFilePath) {
                        $foundKey = $k;
                        break;
                    }
                }
            }
            // 2. Match by f hash if provided
            if ($foundKey === null && $targetFHash) {
                foreach ($decoded as $k => $att) {
                    if (substr(md5($att['file_path'] ?? ''), 0, 12) === $targetFHash) {
                        $foundKey = $k;
                        break;
                    }
                }
            }
            // 3. Fallback: match by integer index
            if ($foundKey === null) {
                $idx = (int) $attachmentIndex;
                if (isset($decoded[$idx])) {
                    $foundKey = $idx;
                }
            }

            if ($foundKey === null) {
                return response()->json(['success' => false, 'message' => 'Attachment not found in message.'], 404);
            }

            $targetFile = $decoded[$foundKey]['file_path'] ?? null;
            if ($targetFile && Storage::exists($targetFile)) {
                Storage::delete($targetFile);
            }

            array_splice($decoded, $foundKey, 1);
            $decoded = array_values($decoded);

            if (count($decoded) === 0) {
                if (empty(trim($chat->message ?? ''))) {
                    $chat->delete();
                    try {
                        broadcast(new ChatMessageDeleted($chatId, $chat->chatable_type, $chat->chatable_id))->toOthers();
                    } catch (\Throwable $e) {}
                    return response()->json(['success' => true, 'deleted_message' => true]);
                } else {
                    $chat->update([
                        'file_path' => null,
                        'file_name' => null,
                        'file_type' => null,
                        'file_size' => null,
                    ]);
                }
            } elseif (count($decoded) === 1) {
                $chat->update([
                    'file_path' => $decoded[0]['file_path'],
                    'file_name' => $decoded[0]['file_name'],
                    'file_type' => $decoded[0]['file_type'],
                    'file_size' => $decoded[0]['file_size'],
                ]);
            } else {
                $chat->update([
                    'file_path' => json_encode($decoded),
                    'file_name' => count($decoded) . ' files attached',
                    'file_type' => 'multipart/mixed',
                    'file_size' => array_sum(array_column($decoded, 'file_size')),
                ]);
            }
        } else {
            // Single attachment — delete the whole file_path
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            if (empty(trim($chat->message ?? ''))) {
                $chat->delete();
                try {
                    broadcast(new ChatMessageDeleted($chatId, $chat->chatable_type, $chat->chatable_id))->toOthers();
                } catch (\Throwable $e) {}
                return response()->json(['success' => true, 'deleted_message' => true]);
            } else {
                $chat->update([
                    'file_path' => null,
                    'file_name' => null,
                    'file_type' => null,
                    'file_size' => null,
                ]);
            }
        }

        $chat->refresh();
        $chat->load(['user', 'replyTo.user']);
        $payload = $this->formatChatPayload($chat);

        return response()->json([
            'success' => true,
            'message_data' => $payload
        ]);
    }

    /**
     * Delete a full chat message and all its attachments.
     */
    public function destroy($chatId)
    {
        $chat = Chat::with('user')->findOrFail($chatId);

        $currentUser = Auth::user();
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $currentUserId = $currentUser->id;
        $currentUserNik = $currentUser->nik;

        // Security check: Only author matching user_id or nik can delete
        $isAuthor = ((int) $chat->user_id === (int) $currentUserId);
        if (!$isAuthor && $chat->user) {
            $isAuthor = ($chat->user->nik === $currentUserNik);
        }

        if (!$isAuthor) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this message.'
            ], 403);
        }

        $type = $chat->chatable_type;
        $id = $chat->chatable_id;

        // Delete physical file(s) if attached
        if ($chat->file_path) {
            $trimmed = trim($chat->file_path);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $att) {
                        if (isset($att['file_path']) && Storage::exists($att['file_path'])) {
                            Storage::delete($att['file_path']);
                        }
                    }
                }
            } else {
                if (Storage::exists($chat->file_path)) {
                    Storage::delete($chat->file_path);
                }
            }
        }

        $chat->delete();

        // Broadcast deleted event (gracefully catch if Reverb is temporarily offline)
        try {
            broadcast(new ChatMessageDeleted($chatId, $type, $id))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Chat Reverb broadcast error on delete: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.'
        ]);
    }

    /**
     * Stream file for preview.
     */
    public function showFile(Request $request, $chatId)
    {
        $chat = Chat::findOrFail($chatId);

        $filePath = $chat->file_path;
        $fileName = $chat->file_name;

        if ($filePath && str_starts_with(trim($filePath), '[')) {
            $decoded = json_decode(trim($filePath), true);
            if (is_array($decoded)) {
                $found = null;
                if ($request->has('f')) {
                    $fHash = $request->query('f');
                    foreach ($decoded as $item) {
                        if (substr(md5($item['file_path'] ?? ''), 0, 12) === $fHash) {
                            $found = $item;
                            break;
                        }
                    }
                }
                if (!$found && $request->has('index')) {
                    $idx = (int) $request->query('index');
                    if (isset($decoded[$idx])) {
                        $found = $decoded[$idx];
                    }
                }
                if (!$found && !empty($decoded)) {
                    $found = $decoded[0];
                }
                if ($found) {
                    $filePath = $found['file_path'] ?? null;
                    $fileName = $found['file_name'] ?? 'preview';
                }
            }
        }

        if (!$filePath || !Storage::exists($filePath)) {
            abort(404, 'File not found');
        }

        return Storage::response($filePath, $fileName, [
            'Content-Disposition' => 'inline; filename="' . $fileName . '"'
        ]);
    }

    /**
     * Download attached file.
     */
    public function download(Request $request, $chatId)
    {
        $chat = Chat::findOrFail($chatId);

        $filePath = $chat->file_path;
        $fileName = $chat->file_name;

        if ($filePath && str_starts_with(trim($filePath), '[')) {
            $decoded = json_decode(trim($filePath), true);
            if (is_array($decoded)) {
                $found = null;
                if ($request->has('f')) {
                    $fHash = $request->query('f');
                    foreach ($decoded as $item) {
                        if (substr(md5($item['file_path'] ?? ''), 0, 12) === $fHash) {
                            $found = $item;
                            break;
                        }
                    }
                }
                if (!$found && $request->has('index')) {
                    $idx = (int) $request->query('index');
                    if (isset($decoded[$idx])) {
                        $found = $decoded[$idx];
                    }
                }
                if (!$found && !empty($decoded)) {
                    $found = $decoded[0];
                }
                if ($found) {
                    $filePath = $found['file_path'] ?? null;
                    $fileName = $found['file_name'] ?? 'download';
                }
            }
        }

        if (!$filePath || !Storage::exists($filePath)) {
            abort(404, 'File not found');
        }

        return Storage::download($filePath, $fileName);
    }

    /**
     * Download all attachments of a message packed as a single ZIP archive.
     */
    public function downloadAll($chatId)
    {
        $chat = Chat::findOrFail($chatId);

        if (!$chat->file_path) {
            abort(404, 'No files to download');
        }

        $files = [];
        $trimmed = trim($chat->file_path);
        if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
            $files = json_decode($trimmed, true) ?? [];
        } else {
            $files[] = [
                'file_path' => $chat->file_path,
                'file_name' => $chat->file_name ?: 'attachment',
            ];
        }

        if (empty($files)) {
            abort(404, 'No files found');
        }

        $zipFileName = 'attachments_chat_' . $chat->id . '_' . date('Ymd_His') . '.zip';
        $tempZip = tempnam(sys_get_temp_dir(), 'chat_zip_');

        $zip = new ZipArchive();
        if ($zip->open($tempZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP archive');
        }

        foreach ($files as $idx => $f) {
            $path = $f['file_path'] ?? '';
            $name = $f['file_name'] ?? ('file_' . ($idx + 1));
            if ($path && Storage::exists($path)) {
                $fileContent = Storage::get($path);
                $zip->addFromString($name, $fileContent);
            }
        }

        $zip->close();

        return response()->download($tempZip, $zipFileName)->deleteFileAfterSend(true);
    }

    /**
     * Format a Chat model into a unified front-end JSON payload.
     */
    public function formatChatPayload($chat)
    {
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
            $trimmed = trim($chat->file_path);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
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

        return [
            'id' => $chat->id,
            'chatable_type' => $chat->chatable_type,
            'chatable_id' => $chat->chatable_id,
            'message' => $chat->message,
            'reply_to' => $replyData,
            'tagged_user_ids' => $chat->tagged_user_ids ?? [],
            'tagged_items' => $chat->tagged_items ?? [],
            'user_id' => $chat->user_id,
            'user_name' => $chat->user->name ?? 'User',
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
     * Get mentionable users and items for tagging (@user and #item).
     */
    public function getMentionables(Request $request, $type, $id)
    {
        // 1. Fetch Users
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ];
            });

        // 2. Fetch Module-specific items (e.g. Products / Parts in WorkOrder)
        $items = [];
        if ($type === 'work_order') {
            $wo = WorkOrder::with('products')->find($id);
            if ($wo && $wo->products) {
                $items = $wo->products->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'code' => $p->customer_part_no,
                        'name' => $p->customer_part_name,
                        'label' => $p->customer_part_no . ' (' . $p->customer_part_name . ')',
                    ];
                })->values();
            }
        } elseif ($type === 'inquiry') {
            $prods = InquiryProduct::where('inquiry_id', $id)->get();
            $items = $prods->map(function ($p) {
                return [
                    'id' => $p->id,
                    'code' => $p->customer_part_no,
                    'name' => $p->customer_part_name,
                    'label' => $p->customer_part_no . ' (' . $p->customer_part_name . ')',
                ];
            })->values();
        }

        return response()->json([
            'success' => true,
            'users' => $users,
            'items' => $items,
        ]);
    }
}
