<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $broadcastQueue = 'sync';

    public $userId;
    public $chatData;

    public function __construct($userId, $chatData)
    {
        $this->userId = $userId;
        $this->chatData = $chatData;
    }

    public function broadcastWith(): array
    {
        // Ensure chatData is properly formatted
        return [
            'chatData' => $this->chatData ?: [],
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.updated';
    }
}
