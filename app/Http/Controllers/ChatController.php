<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
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
        $chat->loadChat();

        return Inertia::render('Chat/Chat', [
            'user' => Auth::user(),
            'chat' => $chat,
            'lastMessageId' => $chat->messages->first() ? $chat->messages->last()->id : 1,
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
                if ($chat->lastMessage) {
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

        try {
            $newMessage = Message::sendMessage($chat, $user, $request->content);
            broadcast(new MessageSent($newMessage));

            return 'ok';
        } catch (Exception $e) {
            return $e;
        }
    }
}
