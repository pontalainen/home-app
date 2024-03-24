<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Message;
use Auth;

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

    public function loadChat()
    {
        $this->load(['users', 'messages' => function ($q) {
            $messages = $q->with(['user'])->latest()->take(25)->get();

            return $messages->reverse();
        }]);
    }

    public function createGroupChat()
    {
        $groupChat = self::create();
        $groupChat->is_group = true;
        $groupChat->save();

        $groupChat->users()->attach($this->users->pluck('id'), ['joined_at' => now()]);

        return $groupChat->id;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_users')
            ->withPivot('nickname', 'bubble_color', 'joined_at', 'left_at');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }
}
