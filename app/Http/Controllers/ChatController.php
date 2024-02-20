<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Error;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $chat = Auth::user()->chats()->first();

        if ($chat) {
            $chat->loadChat();
        }

        return Inertia::render('Chat/Chat', [
            'user' => Auth::user(),
            'chatProp' => $chat,
            'lastMessageId' => $chat && $chat->messages->first() ? $chat->messages->last()->id : 1,
        ]);
    }

    public function chat(Chat $chat)
    {
        $this->authorize('view', $chat);
        $chat->loadChat();

        return Inertia::render('Chat/Chat', [
            'user' => Auth::user(),
            'chatProp' => $chat,
            'lastMessageIdProp' => $chat->messages->first() ? $chat->messages->last()->id : 1,
        ]);
    }

    public function checkChat(Request $request)
    {
        $chat = Chat::whereHas('users', function ($q) use ($request) {
            $q->where('user_id', $request->user);
        })->whereHas('users', function ($q) {
            $q->where('user_id', Auth::id());
        })->first();

        if (!$chat) {
            $chat = Chat::createChat($request->user);
        }

        $this->authorize('view', $chat);
        return $chat;
    }

    public function getChat(Chat $chat)
    {
        $this->authorize('view', $chat);
        $chat->loadChat();
        $chat->otherUser = $chat->users->where('id', '!=', Auth::id())->first();

        return response()->json([
            'chat' => $chat,
            'lastMessageId' => $chat->messages->first() ? $chat->messages->last()->id : 1,
        ]);
    }

    public function getChats()
    {
        $chats = Auth::user()->chats()
            ->with(['users', 'latestMessage', 'latestMessage.user'])
            ->limit(25)
            ->get()
            ->sortByDesc(function ($chat) {
            if ($chat->latestMessage) {
                    return $chat->latestMessage->created_at;
                } else {
                    return $chat->created_at;
                }
            });

        $chats = $chats->map(function ($chat) {
            $chat->otherUser = $chat->users->where('id', '!=', Auth::id())->first();
            return $chat;
        });

        // Converted to array to keep order
        return response()->json($chats->values()->all());
    }

    public function loadMessages(Chat $chat, Request $request)
    {
        $this->authorize('view', $chat);

        $messages = Message::loadMessages($chat, $request->lastMessageId);
        if ($messages->isEmpty()) {
            return 'no more messages';
        }

        return response()
            ->json([
                'messages' => $messages,
                'lastMessageId' => $messages->last()->id,
            ]);
    }

    public function sendMessage(Chat $chat, User $user, Request $request)
    {
        $this->authorize('view', $chat);
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        try {
            $newMessage = Message::sendMessage($chat, $user, $request);
            broadcast(new MessageSent($newMessage));

            return 'ok';
        } catch (Exception $e) {
            return $e;
        }
    }

    public function updateUser(Chat $chat, User $user, Request $request)
    {
        $this->authorize('view', $chat);

        $user = $chat->users()->findOrFail($user->id);

        $request->validate([
            'bubble_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6})$/'],
            'nickname' => ['nullable', 'string', 'max:25'],
        ]);

        if ($request->has('nickname') && $user->pivot->nickname === $request->nickname) {
            return; // If the nickname in the request is the same as the existing nickname, just return
        }

        $attributesToUpdate = $request->only(['bubble_color', 'nickname']);
        $user->pivot->fill($attributesToUpdate)->save();

        $status_type = null;
        if ($request->has('bubble_color')) {
            $status_type = 'bubble_color';
        } elseif ($request->has('nickname')) {
            $status_type = 'nickname';
        }
        $content = $request->input($status_type);

        $messageData = [
            'type' => 'status',
            'status_type' => $status_type,
            'content' => $content,
        ];

        try {
            $newMessage = Message::sendMessage($chat, $user, $messageData);
            broadcast(new MessageSent($newMessage));

            return 'ok';
        } catch (Exception $e) {
            return $e;
        }
    }
}
