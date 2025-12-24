<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/chat/private', [ChatController::class, 'privateChat'])->name('chat.private');
    Route::post('/chat/set-receiver', [ChatController::class, 'setReceiver'])->name('chat.set-receiver');
    Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::post('/chat/typing', [ChatController::class, 'typing'])->name('chat.typing');
    Route::post('/chat/mark-read', [ChatController::class, 'markAsRead'])->name('chat.mark-read');
    Route::post('/chat/check-read-status', [ChatController::class, 'checkReadStatus'])->name('chat.check-read-status');
    Route::get('/chat/poll', [ChatController::class, 'poll'])->name('chat.poll');
    Route::get('/chat/search-users', [ChatController::class, 'searchUsers'])->name('chat.search-users');
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread-count');
    Route::get('/chat/sidebar-chats', [ChatController::class, 'sidebarChats'])->name('chat.sidebar-chats');
    Route::get('/chat/load-older', [ChatController::class, 'loadOlderMessages'])->name('chat.load-older');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
});
