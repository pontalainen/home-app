<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Chat;
use Auth;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function chats()
    {
        return $this->belongsToMany(Chat::class, 'chat_users');
    }

    public function friends()
    {
        return $this->belongsToMany(User::class, 'user_friendships', 'user_one_id', 'user_two_id');
    }

    public function isFriendWith(User $user)
    {
        return $this->friends->contains($user);
    }

    public function addFriend(User $friend)
    {
        if ($this->isFriendWith($friend)) {
            return;
        }
        $this->friends()->attach($friend->id, ['created_at' => now(), 'updated_at' => now()]);
        $friend->friends()->attach(Auth::user()->id, ['created_at' => now(), 'updated_at' => now()]);

        return 'ok';
    }

    public function removeFriend(User $friend)
    {
        $this->friends()->detach($friend->id);
        $friend->friends()->detach(Auth::user()->id);

        return 'ok';
    }

    public function getDiscoveryUsers($search)
    {
        return $this::with(['friends', 'chats'])
            ->where('id', '!=', Auth::user()->id)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->limit(30)
            ->get();
    }

    public function getFriends($search)
    {
        return $this->friends()->with(['friends', 'chats'])
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            })
            ->limit(30)
            ->get();
    }
}
