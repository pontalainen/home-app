<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Chat;
use App\Models\User;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'sent_at',
        'chat_id',
        'user_id',
    ];

    public static function loadMessages(Chat $chat, $lastMessageId)
    {
        $firstMessageId = Message::first()->id;

        // If the lastMessageId is the id of the last message in the database, return an empty collection
        if ($lastMessageId <= $firstMessageId) {
            return collect([]);
        }

        // Otherwise, return the messages that have an id greater than the lastMessageId
        return $chat->messages()
            ->with(['chat', 'user'])
            ->latest()
            ->where('id', '<', $lastMessageId)
            ->where('id', '>=', $firstMessageId)
            ->take(25)
            ->get();
    }

    public static function sendMessage(Chat $chat, User $user, $request)
    {
        $message = new self();

        $attributesToUpdate = $request;
        if (gettype($request) === 'object') {
            $attributesToUpdate = $request->all();
        };

        foreach ($attributesToUpdate as $key => $attr) {
            if ($key === 'image') {
                // Call function to store image
            } else {
                $message->{$key} = $attr;
            }
        }

        $message->sent_at = now();
        $message->chat()->associate($chat);
        $message->user()->associate($user);
        $message->save();

        return $message;
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
