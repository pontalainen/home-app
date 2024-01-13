<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index()
    {
        $chat = Chat::getChat();

        // If you want the messages to be ordered from oldest to newest
        if ($chat) {
            $chat->messages = $chat->messages->reverse()->values();
        }

        return Inertia::render('Chat/Chat', [
            'user' => Auth::user(),
            'chat' => $chat,
        ]);
    }

    public function sendMessage(Chat $chat, User $user, Request $request)
    {
        // dd($chat, $user, $request->all());
        Message::sendMessage($chat, $user, $request->content);
    }
}
