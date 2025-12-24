<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Events\ChatUpdated;
use App\Events\MessageRead as MessageReadEvent;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $groupId = $request->get('group_id');
        $receiverIdFromUrl = $request->get('receiver_id');

        // Priority: URL parameters > session
        // Priority: URL parameters > explicit route > session
        // If accessing /chat directly (no parameters), ALWAYS clear session and show group chat
        if (!$groupId && !$receiverIdFromUrl) {
            // Clear receiver_id from session when accessing /chat directly
            session()->forget('chat_receiver_id');
            $receiverId = null;
            // Show default group chat
            $group = Group::where('name', 'Grup Anime')->first();
            $groupId = $group ? $group->id : null;
        } else if ($receiverIdFromUrl) {
            // If receiver_id is in URL (legacy support), use it and store in session
            session(['chat_receiver_id' => (int) $receiverIdFromUrl]);
            $receiverId = (int) $receiverIdFromUrl;
        } else if ($groupId) {
            // If group_id is explicitly set, clear receiver_id from session
            session()->forget('chat_receiver_id');
            $receiverId = null;
        } else {
            // This should not happen, but if it does, get from session
            // (only for /chat/private route which should not reach here)
            $receiverId = session('chat_receiver_id');
        }

        $messages = [];
        $chatTitle = 'Grup Anime';
        $chatType = 'group';
        $chatAvatar = null;
        $chatName = 'Grup Anime';

        if ($receiverId) {
            // Private chat
            $receiver = User::findOrFail($receiverId);
            $chatTitle = $receiver->name;
            $chatType = 'private';
            $chatAvatar = $receiver->avatar;
            $chatName = $receiver->name;

            // Get messages between current user and receiver - optimized select with eager loading
            $messages = Message::with(['user:id,name,username,avatar', 'reads:message_id,user_id'])
                ->select('id', 'user_id', 'message', 'created_at', 'receiver_id', 'group_id')
                ->where(function ($query) use ($receiverId) {
                    $query->where(function ($q) use ($receiverId) {
                        $q->where('user_id', Auth::id())
                            ->where('receiver_id', $receiverId);
                    })->orWhere(function ($q) use ($receiverId) {
                        $q->where('user_id', $receiverId)
                            ->where('receiver_id', Auth::id());
                    });
                })
                ->whereNull('group_id')
                ->orderBy('created_at')
                ->get()
                ->map(function ($message) {
                    // Add read status for private messages (optimized - using eager loaded relation)
                    if ($message->receiver_id && $message->user_id === Auth::id()) {
                        // Check if receiver has read this message
                        $message->is_read = $message->reads->where('user_id', $message->receiver_id)->isNotEmpty();
                    }
                    return $message;
                });
        } else if ($groupId) {
            // Group chat
            $group = Group::findOrFail($groupId);
            $chatTitle = $group->name;
            $chatType = 'group';
            $chatName = $group->name;

            $messages = Message::with(['user:id,name,username,avatar'])
                ->select('id', 'user_id', 'message', 'created_at', 'receiver_id', 'group_id')
                ->where('group_id', $groupId)
                ->orderBy('created_at')
                ->get();
        }

        // No pagination requested, load all messages
        $hasMoreOlderMessages = false;

        // Get private chat conversations for sidebar with unread count - OPTIMIZED with CACHE
        $currentUserId = Auth::id();

        // Cache sidebar data for 30 seconds to reduce database load (optimized for performance)
        $cacheKey = "user_{$currentUserId}_private_chats";
        $privateChats = Cache::remember($cacheKey, 30, function () use ($currentUserId) {
            return $this->getPrivateChats($currentUserId);
        });

        // Mark messages as read when viewing chat - OPTIMIZED
        if ($receiverId) {
            // Private chat - mark messages as read
            $unreadMessageIds = Message::where('user_id', $receiverId)
                ->where('receiver_id', Auth::id())
                ->whereNull('group_id')
                ->whereDoesntHave('reads', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->pluck('id');

            // Bulk insert reads
            if ($unreadMessageIds->isNotEmpty()) {
                $reads = $unreadMessageIds->map(function ($messageId) {
                    return [
                        'user_id' => Auth::id(),
                        'message_id' => $messageId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                // Use insertOrIgnore to prevent duplicates
                DB::table('message_reads')->insertOrIgnore($reads);

                // Broadcast read status to senders
                $messages = Message::whereIn('id', $unreadMessageIds)->get();
                foreach ($messages as $message) {
                    if ($message->user_id !== Auth::id()) {
                        // Broadcast to sender that their message was read
                        broadcast(new MessageReadEvent($message->id, $message->user_id, Auth::id()));
                    }
                }
            }
        } else if ($groupId) {
            // Group chat - mark messages as read
            $unreadMessageIds = Message::where('group_id', $groupId)
                ->where('user_id', '!=', Auth::id())
                ->whereDoesntHave('reads', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->pluck('id');

            // Bulk insert reads
            if ($unreadMessageIds->isNotEmpty()) {
                $reads = $unreadMessageIds->map(function ($messageId) {
                    return [
                        'user_id' => Auth::id(),
                        'message_id' => $messageId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                // Use insertOrIgnore to prevent duplicates
                DB::table('message_reads')->insertOrIgnore($reads);
            }
        } else {
            // Default group chat (Grup Anime = id 1)
            $defaultGroupId = 1;
            $unreadMessageIds = Message::where('group_id', $defaultGroupId)
                ->where('user_id', '!=', Auth::id())
                ->whereDoesntHave('reads', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->pluck('id');

            // Bulk insert reads
            if ($unreadMessageIds->isNotEmpty()) {
                $reads = $unreadMessageIds->map(function ($messageId) {
                    return [
                        'user_id' => Auth::id(),
                        'message_id' => $messageId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->toArray();

                // Use insertOrIgnore to prevent duplicates
                DB::table('message_reads')->insertOrIgnore($reads);
            }
        }

        // Get receiver_id from session for view (for highlighting active chat)
        $sessionReceiverId = session('chat_receiver_id');

        // Cache view data to speed up rendering
        $viewData = compact('messages', 'privateChats', 'chatTitle', 'chatType', 'chatAvatar', 'chatName', 'groupId', 'receiverId', 'hasMoreOlderMessages');
        $viewData['sessionReceiverId'] = $sessionReceiverId; // For highlighting active chat

        return view('chat.index', $viewData);
    }

    public function privateChat(Request $request)
    {
        // Get receiver_id from session
        $receiverId = session('chat_receiver_id');

        if (!$receiverId) {
            // If no receiver_id in session, redirect to default group chat
            session()->forget('chat_receiver_id');
            return redirect()->route('chat');
        }

        // Verify receiver exists and is not current user
        $receiver = User::find($receiverId);
        if (!$receiver || $receiver->id === Auth::id()) {
            session()->forget('chat_receiver_id');
            return redirect()->route('chat');
        }

        // Render private chat directly (don't call index to avoid clearing session)
        $chatTitle = $receiver->name;
        $chatType = 'private';
        $chatAvatar = $receiver->avatar;
        $chatName = $receiver->name;

        // Get messages between current user and receiver - optimized select with eager loading
        $messages = Message::with(['user:id,name,username,avatar', 'reads:message_id,user_id'])
            ->select('id', 'user_id', 'message', 'created_at', 'receiver_id', 'group_id')
            ->where(function ($query) use ($receiverId) {
                $query->where(function ($q) use ($receiverId) {
                    $q->where('user_id', Auth::id())
                        ->where('receiver_id', $receiverId);
                })->orWhere(function ($q) use ($receiverId) {
                    $q->where('user_id', $receiverId)
                        ->where('receiver_id', Auth::id());
                });
            })
            ->whereNull('group_id')
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) {
                // Add read status for private messages (optimized - using eager loaded relation)
                if ($message->receiver_id && $message->user_id === Auth::id()) {
                    // Check if receiver has read this message
                    $message->is_read = $message->reads->where('user_id', $message->receiver_id)->isNotEmpty();
                }
                return $message;
            });

        // No pagination requested, load all messages
        $hasMoreOlderMessages = false;

        // Get private chat conversations for sidebar with unread count - OPTIMIZED with CACHE
        $currentUserId = Auth::id();

        // Cache sidebar data for 30 seconds to reduce database load (optimized for performance)
        $cacheKey = "user_{$currentUserId}_private_chats";
        $privateChats = Cache::remember($cacheKey, 30, function () use ($currentUserId) {
            return $this->getPrivateChats($currentUserId);
        });

        // Mark messages as read when viewing chat - OPTIMIZED
        $unreadMessageIds = Message::where('user_id', $receiverId)
            ->where('receiver_id', Auth::id())
            ->whereNull('group_id')
            ->whereDoesntHave('reads', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->pluck('id');

        // Bulk insert reads
        if ($unreadMessageIds->isNotEmpty()) {
            $reads = $unreadMessageIds->map(function ($messageId) {
                return [
                    'user_id' => Auth::id(),
                    'message_id' => $messageId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            // Use insertOrIgnore to prevent duplicates
            DB::table('message_reads')->insertOrIgnore($reads);

            // Broadcast read status to senders
            $messagesToBroadcast = Message::whereIn('id', $unreadMessageIds)->get();
            foreach ($messagesToBroadcast as $message) {
                if ($message->user_id !== Auth::id()) {
                    // Broadcast to sender that their message was read
                    broadcast(new MessageReadEvent($message->id, $message->user_id, Auth::id()));
                }
            }
        }

        // Get receiver_id from session for view (for highlighting active chat)
        $sessionReceiverId = session('chat_receiver_id');
        $groupId = null;

        // Cache view data to speed up rendering
        $viewData = compact('messages', 'privateChats', 'chatTitle', 'chatType', 'chatAvatar', 'chatName', 'groupId', 'receiverId', 'hasMoreOlderMessages');
        $viewData['sessionReceiverId'] = $sessionReceiverId; // For highlighting active chat

        return view('chat.index', $viewData);
    }

    public function setReceiver(Request $request)
    {
        $receiverId = $request->get('receiver_id');

        // If receiver_id is null, clear session (for navigating to group chat)
        if ($receiverId === null || $receiverId === 'null' || $receiverId === '') {
            session()->forget('chat_receiver_id');
            $request->session()->save();
            return response()->json(['success' => true, 'receiver_id' => null]);
        }

        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
        ]);

        $receiverId = (int) $receiverId;

        // Verify receiver is not current user
        if ($receiverId === Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Cannot chat with yourself'], 400);
        }

        // Verify receiver exists
        $receiver = User::find($receiverId);
        if (!$receiver) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Store receiver_id in session
        session(['chat_receiver_id' => $receiverId]);

        // Ensure session is saved
        $request->session()->save();

        return response()->json(['success' => true, 'receiver_id' => $receiverId]);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['users' => []]);
        }

        $users = User::where('id', '!=', Auth::id())
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('username', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'avatar' => $user->avatar,
                    'initials' => $user->initials,
                ];
            });

        return response()->json(['users' => $users]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'group_id' => 'nullable|exists:groups,id',
            'receiver_id' => 'nullable|exists:users,id',
        ]);

        $message = Message::create([
            'user_id' => Auth::id(),
            'group_id' => $request->group_id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
        ]);

        $message->load('user');

        // Mark message as read by sender (they can see it immediately)
        MessageRead::firstOrCreate([
            'user_id' => Auth::id(),
            'message_id' => $message->id,
        ]);

        // If private chat, update sidebar for both users (always, even for first message)
        // IMPORTANT: Do this BEFORE broadcasting MessageSent to ensure ChatUpdated arrives first
        if ($request->receiver_id) {
            $receiver = User::find($request->receiver_id);
            if ($receiver) {
                // Clear cache for both users
                Cache::forget("user_{$receiver->id}_private_chats");
                Cache::forget("user_" . Auth::id() . "_private_chats");

                // Get chat data for receiver (they will see sender in their sidebar)
                // IMPORTANT: This must happen AFTER message is created so last_message exists
                $receiverChatData = $this->getChatDataForUser($receiver->id, Auth::id());
                if ($receiverChatData) {
                    // Broadcast to receiver's user channel to update their sidebar
                    // This ensures sender appears in receiver's sidebar immediately
                    broadcast(new ChatUpdated($receiver->id, $receiverChatData));
                }

                // Also update sender's sidebar (important for first message - they will see receiver)
                $senderChatData = $this->getChatDataForUser(Auth::id(), $receiver->id);
                if ($senderChatData) {
                    // Broadcast to sender's user channel to update their sidebar
                    broadcast(new ChatUpdated(Auth::id(), $senderChatData));
                }
            }
        }

        // Broadcast message to chat channel (after sidebar updates)
        broadcast(new MessageSent($message));

        // Ensure message data is complete
        $messageData = [
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
        if ($request->receiver_id && $message->user_id === Auth::id()) {
            $messageData['is_read'] = false; // Initially not read
        }

        $responseData = [
            'success' => true,
            'message' => $messageData,
        ];

        // If private chat, include chat data for immediate sidebar update
        if ($request->receiver_id) {
            $senderChatData = $this->getChatDataForUser(Auth::id(), $request->receiver_id);
            if ($senderChatData) {
                $responseData['chatData'] = $senderChatData;
            }
        }

        return response()->json($responseData);
    }

    public function typing(Request $request)
    {
        $request->validate([
            'group_id' => 'nullable|exists:groups,id',
            'receiver_id' => 'nullable|exists:users,id',
        ]);

        broadcast(new UserTyping(Auth::user(), $request->group_id, $request->receiver_id))->toOthers();

        return response()->json(['success' => true]);
    }

    public function poll(Request $request)
    {
        $lastId = $request->get('last_id', 0);
        $groupId = $request->get('group_id');
        $receiverId = $request->get('receiver_id');

        // Optimized: Eager load user and reads relation
        $query = Message::with(['user:id,name,username,avatar', 'reads:message_id,user_id'])
            ->where('id', '>', $lastId);

        if ($receiverId) {
            // Private chat - get messages between current user and receiver
            $query->where(function ($q) use ($receiverId) {
                $q->where(function ($query) use ($receiverId) {
                    $query->where('user_id', Auth::id())
                        ->where('receiver_id', $receiverId);
                })->orWhere(function ($query) use ($receiverId) {
                    $query->where('user_id', $receiverId)
                        ->where('receiver_id', Auth::id());
                });
            })
                ->whereNull('group_id');
        } else if ($groupId) {
            // Group chat
            $query->where('group_id', $groupId);
        } else {
            // Default group chat (Grup Anime = id 1)
            $query->where('group_id', 1);
        }

        $messages = $query->latest()
            ->take(40)
            ->get()
            ->reverse()
            ->map(function ($message) {
                $messageData = [
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

                // Add read status for private messages sent by current user (optimized - using eager loaded relation)
                if ($message->receiver_id && $message->user_id === Auth::id()) {
                    $messageData['is_read'] = $message->reads->where('user_id', $message->receiver_id)->isNotEmpty();
                }

                return $messageData;
            });

        return response()->json(['messages' => $messages]);
    }

    public function loadOlderMessages(Request $request)
    {
        $beforeId = $request->get('before_id', 0);
        $beforeCreatedAt = $request->get('before_created_at');
        $groupId = $request->get('group_id');
        $receiverId = $request->get('receiver_id');

        if (!$beforeId) {
            return response()->json(['messages' => [], 'has_more' => false]);
        }

        // Optimized: Eager load user and reads relation
        $query = Message::with(['user:id,name,username,avatar', 'reads:message_id,user_id']);

        // Use created_at for better pagination if provided, otherwise use id
        if ($beforeCreatedAt) {
            $query->where('created_at', '<', $beforeCreatedAt);
        } else {
            $query->where('id', '<', $beforeId);
        }

        if ($receiverId) {
            // Private chat - get messages between current user and receiver
            $query->where(function ($q) use ($receiverId) {
                $q->where(function ($query) use ($receiverId) {
                    $query->where('user_id', Auth::id())
                        ->where('receiver_id', $receiverId);
                })->orWhere(function ($query) use ($receiverId) {
                    $query->where('user_id', $receiverId)
                        ->where('receiver_id', Auth::id());
                });
            })
                ->whereNull('group_id');
        } else if ($groupId) {
            // Group chat
            $query->where('group_id', $groupId);
        } else {
            // Default group chat (Grup Anime = id 1)
            $query->where('group_id', 1);
        }

        // Get 40 older messages (ordered by latest first, then reverse to get chronological order)
        $messages = $query->latest()
            ->take(40)
            ->get()
            ->reverse();

        // Check if there are more messages
        $hasMore = false;
        if ($messages->count() > 0) {
            $oldestMessage = $messages->first();
            $hasMoreQuery = Message::query();
            if ($receiverId) {
                $hasMoreQuery->where(function ($q) use ($receiverId) {
                    $q->where(function ($query) use ($receiverId) {
                        $query->where('user_id', Auth::id())
                            ->where('receiver_id', $receiverId);
                    })->orWhere(function ($query) use ($receiverId) {
                        $query->where('user_id', $receiverId)
                            ->where('receiver_id', Auth::id());
                    });
                })->whereNull('group_id');
            } else if ($groupId) {
                $hasMoreQuery->where('group_id', $groupId);
            } else {
                $hasMoreQuery->where('group_id', 1);
            }
            $hasMore = $hasMoreQuery->where('created_at', '<', $oldestMessage->created_at)->exists();
        }

        $messages = $messages->map(function ($message) {
            $messageData = [
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

            // Add read status for private messages sent by current user
            if ($message->receiver_id && $message->user_id === Auth::id()) {
                $messageData['is_read'] = $message->reads->where('user_id', $message->receiver_id)->isNotEmpty();
            }

            return $messageData;
        });

        return response()->json([
            'messages' => $messages,
            'has_more' => $hasMore
        ]);
    }

    public function unreadCount()
    {
        // Get unread counts for all private chats and group chats - OPTIMIZED with DB facade
        $currentUserId = Auth::id();

        // Private chat unread counts
        $privateUnreadCounts = DB::table('messages')
            ->selectRaw('
                CASE 
                    WHEN user_id = ? THEN receiver_id 
                    ELSE user_id 
                END as other_user_id,
                COUNT(*) as unread_count
            ', [$currentUserId])
            ->where(function ($query) use ($currentUserId) {
                $query->where('receiver_id', $currentUserId)
                    ->where('user_id', '!=', $currentUserId);
            })
            ->whereNull('group_id')
            ->whereNotExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('message_reads')
                    ->whereColumn('message_reads.message_id', 'messages.id')
                    ->where('message_reads.user_id', $currentUserId);
            })
            ->groupBy('other_user_id')
            ->pluck('unread_count', 'other_user_id');

        // Group chat unread counts
        $groupUnreadCounts = DB::table('messages')
            ->selectRaw('group_id, COUNT(*) as unread_count')
            ->whereNotNull('group_id')
            ->where('user_id', '!=', $currentUserId)
            ->whereNotExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('message_reads')
                    ->whereColumn('message_reads.message_id', 'messages.id')
                    ->where('message_reads.user_id', $currentUserId);
            })
            ->groupBy('group_id')
            ->pluck('unread_count', 'group_id');

        return response()->json([
            'unread_counts' => $privateUnreadCounts,
            'group_unread_counts' => $groupUnreadCounts
        ]);
    }

    public function sidebarChats()
    {
        // Get private chat conversations for sidebar - for polling updates
        $currentUserId = Auth::id();

        // Don't use cache for polling - always get fresh data
        $privateChats = $this->getPrivateChats($currentUserId);

        return response()->json(['chats' => $privateChats]);
    }

    public function checkReadStatus(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id',
        ]);

        $currentUserId = Auth::id();
        $messageIds = $request->message_ids;

        if (empty($messageIds)) {
            return response()->json(['messages' => []]);
        }

        // Optimized: Get messages and read status in one query with eager loading
        $messages = Message::whereIn('id', $messageIds)
            ->where('user_id', $currentUserId) // Only our own messages
            ->whereNotNull('receiver_id') // Only private messages
            ->with(['reads' => function ($query) {
                $query->select('message_id', 'user_id');
            }])
            ->get()
            ->map(function ($message) {
                // Check if receiver has read this message (optimized - using eager loaded relation)
                $isRead = $message->reads->where('user_id', $message->receiver_id)->isNotEmpty();
                return [
                    'id' => $message->id,
                    'is_read' => $isRead,
                ];
            });

        return response()->json(['messages' => $messages]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'message_ids' => 'required|array',
            'message_ids.*' => 'exists:messages,id',
        ]);

        $currentUserId = Auth::id();
        $messageIds = $request->message_ids;

        // Get messages to find senders
        $messages = Message::whereIn('id', $messageIds)->get();

        // Mark messages as read
        $reads = collect($messageIds)->map(function ($messageId) use ($currentUserId) {
            return [
                'user_id' => $currentUserId,
                'message_id' => $messageId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->toArray();

        DB::table('message_reads')->insertOrIgnore($reads);

        // Broadcast read status to senders for private messages
        foreach ($messages as $message) {
            if ($message->receiver_id && $message->user_id !== $currentUserId) {
                // This is a private message sent TO current user
                // Broadcast to sender that their message was read
                broadcast(new MessageReadEvent($message->id, $message->user_id, $currentUserId));
            }
        }

        // Clear cache for sidebar
        Cache::forget("user_{$currentUserId}_private_chats");

        return response()->json(['success' => true]);
    }

    private function getPrivateChats($currentUserId)
    {
        // Get all unique chat partners in one query - using DB facade
        $chatPartners = DB::table('messages')
            ->selectRaw('
                CASE 
                    WHEN user_id = ? THEN receiver_id 
                    ELSE user_id 
                END as other_user_id
            ', [$currentUserId])
            ->where(function ($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId)
                    ->orWhere('receiver_id', $currentUserId);
            })
            ->whereNotNull('receiver_id')
            ->whereNull('group_id')
            ->distinct()
            ->pluck('other_user_id')
            ->filter()
            ->unique();

        if ($chatPartners->isEmpty()) {
            return collect([]);
        }

        // Load all users in one query - select only needed columns
        $users = User::select('id', 'name', 'username', 'avatar')
            ->whereIn('id', $chatPartners)
            ->get()
            ->keyBy('id');

        // Get all last messages - optimized with DB facade to avoid GROUP BY issues
        $lastMessageIds = DB::table('messages')
            ->selectRaw('
                CASE 
                    WHEN user_id = ? THEN receiver_id 
                    ELSE user_id 
                END as other_user_id,
                MAX(id) as last_message_id
            ', [$currentUserId])
            ->where(function ($q) use ($currentUserId) {
                $q->where('user_id', $currentUserId)
                    ->orWhere('receiver_id', $currentUserId);
            })
            ->whereNotNull('receiver_id')
            ->whereNull('group_id')
            ->groupBy('other_user_id')
            ->pluck('last_message_id', 'other_user_id');

        // Load all last messages in one query - optimized select
        $lastMessages = Message::with(['user:id,name,username,avatar'])
            ->select('id', 'user_id', 'message', 'created_at', 'receiver_id', 'group_id')
            ->whereIn('id', $lastMessageIds->values())
            ->get()
            ->keyBy(function ($msg) use ($currentUserId) {
                return $msg->user_id === $currentUserId ? $msg->receiver_id : $msg->user_id;
            });

        // Get unread counts in one query - using DB facade to avoid GROUP BY issues
        $unreadCounts = DB::table('messages')
            ->selectRaw('
                CASE 
                    WHEN user_id = ? THEN receiver_id 
                    ELSE user_id 
                END as other_user_id,
                COUNT(*) as unread_count
            ', [$currentUserId])
            ->where(function ($query) use ($currentUserId) {
                $query->where('receiver_id', $currentUserId)
                    ->where('user_id', '!=', $currentUserId);
            })
            ->whereNull('group_id')
            ->whereNotExists(function ($query) use ($currentUserId) {
                $query->select(DB::raw(1))
                    ->from('message_reads')
                    ->whereColumn('message_reads.message_id', 'messages.id')
                    ->where('message_reads.user_id', $currentUserId);
            })
            ->groupBy('other_user_id')
            ->pluck('unread_count', 'other_user_id');

        // Build result array
        return $chatPartners->map(function ($otherUserId) use ($users, $lastMessages, $unreadCounts) {
            $otherUser = $users->get($otherUserId);
            if (!$otherUser) return null;

            $lastMessage = $lastMessages->get($otherUserId);

            return [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'username' => $otherUser->username,
                'avatar' => $otherUser->avatar,
                'initials' => $otherUser->initials,
                'unread_count' => $unreadCounts->get($otherUserId, 0),
                'last_message' => $lastMessage ? [
                    'message' => $lastMessage->message,
                    'created_at' => $lastMessage->created_at,
                ] : null,
            ];
        })
            ->filter()
            ->sortByDesc(function ($chat) {
                return $chat['last_message'] ? $chat['last_message']['created_at']->timestamp : 0;
            })
            ->values();
    }

    private function getChatDataForUser($userId, $otherUserId)
    {
        $otherUser = User::find($otherUserId);
        if (!$otherUser) return null;

        $lastMessage = Message::where(function ($q) use ($otherUserId, $userId) {
            $q->where(function ($query) use ($userId, $otherUserId) {
                $query->where('user_id', $userId)
                    ->where('receiver_id', $otherUserId);
            })->orWhere(function ($query) use ($userId, $otherUserId) {
                $query->where('user_id', $otherUserId)
                    ->where('receiver_id', $userId);
            });
        })
            ->whereNull('group_id')
            ->latest()
            ->first();

        $unreadCount = Message::where('user_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->whereNull('group_id')
            ->whereDoesntHave('reads', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        return [
            'id' => $otherUser->id,
            'name' => $otherUser->name,
            'username' => $otherUser->username,
            'avatar' => $otherUser->avatar,
            'initials' => $otherUser->initials,
            'unread_count' => $unreadCount,
            'last_message' => $lastMessage ? [
                'message' => $lastMessage->message,
                'created_at' => $lastMessage->created_at->toISOString(),
            ] : null,
        ];
    }
}
