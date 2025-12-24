<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function getOtherUserAttribute(): User
    {
        return $this->user_one_id === auth()->id() ? $this->userTwo : $this->userOne;
    }

    public function getLastMessageAttribute()
    {
        return Message::where(function($query) {
            $query->where('user_id', $this->user_one_id)
                  ->orWhere('user_id', $this->user_two_id);
        })
        ->latest()
        ->first();
    }
}
