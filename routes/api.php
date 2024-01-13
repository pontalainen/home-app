<?php

use App\Http\Controllers\ChatController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::name('chat::')->group(function () {
    // Route::post('/chat/{chat}/messages', [ChatController::class, 'loadMessages'])->name('loadMessages');
    Route::post('chat/{chat}/user/{user}/sendMessage', [ChatController::class, 'sendMessage'])->name('sendMessage');
});
