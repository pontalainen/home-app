<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Message;

class Chat extends Model
{
    use HasFactory;

    public static function createChat($userId)
    {
        $chat = self::create();
        $chat->users()->attach(auth()->user()->id, ['joined_at' => now()]);
        $chat->users()->attach($userId, ['joined_at' => now()]);

        return $chat;
    }

    public static function getChat()
    {


        return self::with(['users', 'messages' => function ($q) {
            $messages = $q->with('user')->latest()->take(25)->get();
            return $messages->reverse();
        }])->first();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_users');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
