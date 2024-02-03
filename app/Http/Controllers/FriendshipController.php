<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FriendshipController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return Inertia::render('Friends/Friends', [
            'user' => Auth::user()->load('chats', 'friends'),
            'modeProp' => 'discover'
        ]);
    }

    public function myFriends()
    {
        return Inertia::render('Friends/Friends', [
            'user' => Auth::user()->load('chats', 'friends'),
            'modeProp' => 'myFriends'
        ]);
    }

    public function getUsers(Request $request)
    {
        return User::getDiscoveryUsers($request->search);
    }

    public function getFriends()
    {
        return Auth::user()->friends()->with(['friends', 'chats'])->get();
    }

    public function toggleFriendship(User $user, Request $request)
    {
        if ($request->type === 'add') {
            return Auth::user()->addFriend($user);
        } else {
            return Auth::user()->removeFriend($user);
        }
    }
}
