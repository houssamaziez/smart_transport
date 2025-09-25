<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ChatController;

Route::get('/chat', [ChatController::class, 'index']);
// Route::post('/send-message', [ChatController::class, 'sendMessage']);


Route::post('/send-message', [ChatController::class, 'sendMessage']);
Route::get('/chat-receiver', function () {
    return view('chat-receiver');
});
