<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $broadcastQueue = 'sync';

    public $messageId;
    public $userId;
    public $receiverId;

    /**
     * Create a new event instance.
     */
    public function __construct($messageId, $userId, $receiverId)
    {
        $this->messageId = $messageId;
        $this->userId = $userId;
        $this->receiverId = $receiverId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast to sender's user channel so they know their message was read
        return [
            new Channel('user.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'receiver_id' => $this->receiverId,
            'is_read' => true,
        ];
    }
}
