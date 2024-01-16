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
        return Inertia::render('Friends/Discover', [
            'user' => Auth::user()->load('chats', 'friends'),
        ]);
    }

    public function toggleFriendship(User $user, Request $request)
    {
        dd($request->all(), $user);
    }

    public function getUsers(User $user, Request $request)
    {
        dd('getUsers');
    }
}
