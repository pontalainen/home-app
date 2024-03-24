<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FriendshipController;
use App\Models\Chat;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
    return redirect()->route('login');
})->name('home');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::name('profile::')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('destroy');
});

Route::name('chat::')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('index');
    Route::get('/chat/get-chats', [ChatController::class, 'getChats'])->name('getChats');
    Route::get('/chat/get-chat/{chat}', [ChatController::class, 'getChat'])->name('getChat');

    Route::get('/chat/{chat}/get-available-members', [ChatController::class, 'getAvailableMembers'])->name('getAvailableMembers');

    Route::get('/chat/{chat}', [ChatController::class, 'chat'])->name('chat');
    Route::post('/chat/check-chat', [ChatController::class, 'checkAndCreateChat'])->name('checkChat');

    Route::post('/chat/{chat}/create-group', [ChatController::class, 'createGroupChat'])->name('createGroupChat');
    Route::post('/chat/{chat}/add-members', [ChatController::class, 'addMembers'])->name('addMembers');

    Route::post('/chat/{chat}/messages', [ChatController::class, 'loadMessages'])->name('loadMessages');
    Route::post('chat/{chat}/sendMessage', [ChatController::class, 'sendMessage'])->name('sendMessage');

    Route::put('/chat/{chat}/user/{user}/update', [ChatController::class, 'updateUser'])->name('updateUser');
});

Route::name('friends::')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/friends', [FriendshipController::class, 'index'])->name('myFriends');
    Route::get('/friends/discover', [FriendshipController::class, 'discover'])->name('discover');

    Route::post('/friends/get-users', [FriendshipController::class, 'getUsers'])->name('getUsers');
    Route::post('/friends/get-friends', [FriendshipController::class, 'getFriends'])->name('getFriends');

    Route::post('/friends/toggle-friendship/{user}', [FriendshipController::class, 'toggleFriendship'])->name('toggleFriendship');
});

require __DIR__ . '/auth.php';
