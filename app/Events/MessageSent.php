<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue on which to place the broadcasting job.
     *
     * @var string
     */
    public $broadcastQueue = 'sync';

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = [
            'id' => $message->id,
            'user_id' => $message->user_id,
            'group_id' => $message->group_id,
            'receiver_id' => $message->receiver_id,
            'message' => $message->message,
            'created_at' => $message->created_at->toISOString(),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'username' => $message->user->username,
                'avatar' => $message->user->avatar,
                'initials' => $message->user->initials,
            ],
        ];
        
        // Add read status for private messages
        if ($message->receiver_id && $message->user_id) {
            $this->message['is_read'] = $message->isReadBy($message->receiver_id);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // If group message, broadcast to group channel
        if ($this->message['group_id']) {
            $channels[] = new Channel('chat.group.' . $this->message['group_id']);
        }
        
        // If private message, broadcast to multiple channels
        if ($this->message['receiver_id']) {
            $userId = $this->message['user_id'];
            $receiverId = $this->message['receiver_id'];
            
            // 1. Broadcast to private chat channel (for users viewing that specific chat)
            $channelName = 'chat.private.' . min($userId, $receiverId) . '.' . max($userId, $receiverId);
            $channels[] = new Channel($channelName);
            
            // 2. Broadcast to receiver's user-specific channel (so they receive it even if on different page)
            $channels[] = new Channel('user.' . $receiverId);
        }
        
        // If no channels added, use fallback
        if (empty($channels)) {
            $channels[] = new Channel('chat');
        }
        
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
