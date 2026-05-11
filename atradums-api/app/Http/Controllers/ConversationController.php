<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Profile;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = request()->user()->id;

        $convs = Conversation::query()
            ->where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->orderByDesc('updated_at')
            ->get();

        $convIds = $convs->pluck('id');
        $messages = $convIds->isEmpty()
            ? collect()
            : Message::query()
                ->whereIn('conversation_id', $convIds)
                ->orderBy('created_at')
                ->get();

        $otherUserIds = $convs->map(function ($c) use ($userId) {
            return (int) ($c->sender_id === $userId ? $c->receiver_id : $c->sender_id);
        })->unique()->values();

        $profiles = $otherUserIds->isEmpty()
            ? collect()
            : Profile::query()
                ->whereIn('user_id', $otherUserIds)
                ->get()
                ->keyBy('user_id');

        return $convs->map(function ($c) use ($userId, $messages, $profiles) {
            $otherUserId = $c->sender_id === $userId ? $c->receiver_id : $c->sender_id;
            $profile = $profiles->get($otherUserId);

            return [
                'id' => $c->id,
                'listing_id' => $c->listing_id,
                'listing_title' => $c->listing_title,
                'sender_id' => $c->sender_id,
                'receiver_id' => $c->receiver_id,
                'other_user' => [
                    'user_id' => $otherUserId,
                    'name' => $profile?->name,
                    'avatar_url' => $profile?->avatar_url,
                ],
                'messages' => $messages->where('conversation_id', $c->id)->values(),
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
            ];
        })->values();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'listing_id' => ['nullable', 'integer'],
            'listing_title' => ['nullable', 'string', 'max:255'],
            'receiver_id' => ['required', 'integer'],
        ]);

        $senderId = $request->user()->id;
        $receiverId = (int) $data['receiver_id'];

        $existing = Conversation::query()
            ->where('listing_id', $data['listing_id'] ?? null)
            ->where(function ($q) use ($senderId, $receiverId) {
                $q->where([
                    ['sender_id', '=', $senderId],
                    ['receiver_id', '=', $receiverId],
                ])->orWhere([
                    ['sender_id', '=', $receiverId],
                    ['receiver_id', '=', $senderId],
                ]);
            })
            ->first();

        if ($existing) {
            return response()->json($existing, 200);
        }

        $conv = Conversation::create([
            'listing_id' => $data['listing_id'] ?? null,
            'listing_title' => $data['listing_title'] ?? '',
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
        ]);

        return response()->json($conv, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
