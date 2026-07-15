<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\InquiryProductChat;
use App\Events\InquiryProductChatMessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class InquiryProductChatController extends Controller
{
    /**
     * Fetch chat history for a specific product.
     */
    public function index(Request $request, $productId)
    {
        $query = InquiryProductChat::with('user')
            ->where('inquiry_product_id', $productId);

        if ($request->has('before_id')) {
            $query->where('id', '<', $request->input('before_id'));
        }

        // Load 20 chats at a time
        $messages = $query->orderBy('id', 'desc')
            ->take(20)
            ->get()
            ->reverse()
            ->values();

        $formatted = $messages->map(function ($chat) {
            return [
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
        });

        return response()->json([
            'success' => true,
            'messages' => $formatted,
        ]);
    }

    /**
     * Store a new chat message.
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // 10MB Limit
        ]);

        if (!$request->input('message') && !$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send an empty message.'
            ], 422);
        }

        $fileData = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize();

            // Store inside public/inquiry_chats/{productId} folder
            $path = $file->store("public/inquiry_chats/{$productId}");

            $fileData = [
                'file_path' => $path,
                'file_name' => $originalName,
                'file_type' => $mimeType,
                'file_size' => $size,
            ];
        }

        $chat = InquiryProductChat::create(array_merge([
            'inquiry_product_id' => $productId,
            'user_id' => Auth::user()->id,
            'message' => $request->input('message'),
        ], $fileData));

        $chat->load('user');

        // Broadcast event to other users
        broadcast(new InquiryProductChatMessageSent($chat))->toOthers();

        return response()->json([
            'success' => true,
            'message' => [
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
            ]
        ]);
    }

    /**
     * Serve attached file inline for preview.
     */
    public function showFile($chatId)
    {
        $chat = InquiryProductChat::findOrFail($chatId);
        
        if (!$chat->file_path || !Storage::exists($chat->file_path)) {
            abort(404, 'File not found or has been deleted.');
        }

        return Storage::response($chat->file_path);
    }

    /**
     * Download attached file.
     */
    public function download($chatId)
    {
        $chat = InquiryProductChat::findOrFail($chatId);
        
        if (!$chat->file_path || !Storage::exists($chat->file_path)) {
            abort(404, 'File not found or has been deleted.');
        }

        return Storage::download($chat->file_path, $chat->file_name);
    }

    /**
     * Delete a chat message and its associated attachment.
     */
    public function destroy($chatId)
    {
        $chat = InquiryProductChat::findOrFail($chatId);

        // Only the message owner can delete it
        if ((int) $chat->user_id !== (int) Auth::user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this message.'
            ], 403);
        }

        $productId = $chat->inquiry_product_id;

        if ($chat->file_path && Storage::exists($chat->file_path)) {
            Storage::delete($chat->file_path);
        }

        $chat->delete();

        // Broadcast deletion to other users
        broadcast(new \App\Events\InquiryProductChatMessageDeleted($chatId, $productId))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.'
        ]);
    }
}
