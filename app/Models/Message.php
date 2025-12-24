<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'user_id',
        'group_id',
        'receiver_id',
        'message',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function isGroupMessage(): bool
    {
        return $this->group_id !== null;
    }

    public function isPrivateMessage(): bool
    {
        return $this->receiver_id !== null;
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    public function isReadBy($userId)
    {
        return $this->reads()->where('user_id', $userId)->exists();
    }
}
