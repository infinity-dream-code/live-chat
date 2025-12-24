<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public chat channel - accessible to all authenticated users
Broadcast::channel('chat', function ($user) {
    return true; // Allow all authenticated users
});

// Group chat channels
Broadcast::channel('chat.group.{groupId}', function ($user, $groupId) {
    return true; // Allow all authenticated users for group chats
});

// Private chat channels - only allow the two users involved
Broadcast::channel('chat.private.{userId1}.{userId2}', function ($user, $userId1, $userId2) {
    $currentUserId = (int) $user->id;
    $id1 = (int) $userId1;
    $id2 = (int) $userId2;
    
    // Allow if current user is one of the two users in the private chat
    return $currentUserId === $id1 || $currentUserId === $id2;
});

// User-specific channel for sidebar updates
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
