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
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');

    Route::post('/chat/{chat}/messages', [ChatController::class, 'loadMessages'])->name('loadMessages');
    Route::post('chat/{chat}/user/{user}/sendMessage', [ChatController::class, 'sendMessage'])->name('sendMessage');
});

Route::name('friends::')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/friends', [FriendshipController::class, 'index'])->name('friends');
    Route::post('/friends/toggle-friendship/{user}', [FriendshipController::class, 'toggleFriendship'])->name('toggleFriendship');

    // Route::post('/chat/{chat}/messages', [ChatController::class, 'loadMessages'])->name('loadMessages');
    // Route::post('chat/{chat}/user/{user}/sendMessage', [ChatController::class, 'sendMessage'])->name('sendMessage');
});

require __DIR__ . '/auth.php';
