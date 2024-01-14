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
    public function index()
    {
        $this->middleware('auth');

        $chat = Chat::getChat();
        $this->authorize('view', $chat);

        return Inertia::render('Chat/Chat', [
            'user' => Auth::user(),
            'chat' => $chat,
            'lastMessageId' => $chat->messages->first() ? $chat->messages->last()->id : 1,
        ]);
    }

    public function loadMessages(Chat $chat, Request $request)
    {
        $this->middleware('auth');
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
        $this->middleware('auth');
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
