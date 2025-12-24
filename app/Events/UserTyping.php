<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The name of the queue on which to place the broadcasting job.
     *
     * @var string
     */
    public $broadcastQueue = 'sync';

    public $user;
    public $group_id;
    public $receiver_id;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, $group_id = null, $receiver_id = null)
    {
        $this->user = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
        ];
        $this->group_id = $group_id;
        $this->receiver_id = $receiver_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // If group typing, broadcast to group channel
        if ($this->group_id) {
            return [
                new Channel('chat.group.' . $this->group_id),
            ];
        }
        
        // If private typing, broadcast to private channel
        if ($this->receiver_id) {
            $userId = $this->user['id'];
            $receiverId = $this->receiver_id;
            
            $channelName = 'chat.private.' . min($userId, $receiverId) . '.' . max($userId, $receiverId);
            
            return [
                new Channel($channelName),
            ];
        }
        
        // Fallback to general chat channel
        return [
            new Channel('chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'typing';
    }
}
