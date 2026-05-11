<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->sender_id !== $userId && $conversation->receiver_id !== $userId) {
            abort(403);
        }

        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->get();
    }

    public function store(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->sender_id !== $userId && $conversation->receiver_id !== $userId) {
            abort(403);
        }

        $data = $request->validate([
            'text' => ['required', 'string'],
        ]);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userId,
            'text' => $data['text'],
            'read' => false,
        ]);

        $conversation->touch();

        return response()->json($msg, 201);
    }

    public function markConversationAsRead(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        if ($conversation->sender_id !== $userId && $conversation->receiver_id !== $userId) {
            abort(403);
        }

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $userId)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['success' => true]);
    }
}
