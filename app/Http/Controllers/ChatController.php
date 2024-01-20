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
        $chat = Auth::user()
            ->chats()
            ->with('messages')
            ->first();

        return Inertia::render('Chat/Chat', [
            'user' => Auth::user(),
            'chat' => $chat,
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
        return $chat->id;
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
