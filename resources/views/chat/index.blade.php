@extends('layouts.chat')

@section('content')
<div class="flex-1 flex flex-col h-full min-h-0 chat-content-wrapper">
    <!-- Chat Header (Navbar) -->
    <div class="bg-white border-b border-gray-200 px-4 py-3 flex-shrink-0">
        <div class="flex items-center space-x-3">
            @if($chatType === 'private' && $chatAvatar)
                <img src="{{ Storage::url($chatAvatar) }}" 
                    alt="{{ $chatTitle }}" 
                    class="w-10 h-10 rounded-full object-cover"
                    loading="lazy"
                    decoding="async">
            @elseif($chatType === 'private')
                <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-sm">
                    {{ substr($chatTitle, 0, 1) }}
                </div>
            @else
                <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-sm">
                    GA
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900">{{ $chatTitle }}</p>
                <p class="text-xs text-gray-500">{{ $chatType === 'group' ? 'Group chat' : 'Private chat' }}</p>
            </div>
        </div>
    </div>

    <!-- Messages Area -->
    <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50" style="min-height: 0;">
        <!-- Loading Indicator for Older Messages -->
        <div id="loading-older-messages" class="px-4 py-2 text-center text-sm text-gray-500 hidden">
            Loading older messages...
        </div>
        @php
            $prevDate = null;
        @endphp
        @foreach($messages as $message)
            @php
                $currentDate = $message->created_at->setTimezone('Asia/Jakarta')->format('Y-m-d');
                $showDateSeparator = $prevDate === null || $prevDate !== $currentDate;
                if ($showDateSeparator) {
                    $prevDate = $currentDate;
                    $today = \Carbon\Carbon::today('Asia/Jakarta')->format('Y-m-d');
                    $yesterday = \Carbon\Carbon::yesterday('Asia/Jakarta')->format('Y-m-d');
                    
                    if ($currentDate === $today) {
                        $dateLabel = 'Hari ini';
                    } elseif ($currentDate === $yesterday) {
                        $dateLabel = 'Kemarin';
                    } else {
                        $dateLabel = $message->created_at->setTimezone('Asia/Jakarta')->translatedFormat('l, d F Y');
                    }
                }
            @endphp
            @if($showDateSeparator)
                <div class="flex justify-center my-4" data-date-separator="{{ $currentDate }}">
                    <div class="px-3 py-1 bg-gray-200 rounded-full">
                        <p class="text-xs text-gray-600 font-medium">{{ $dateLabel }}</p>
                    </div>
                </div>
            @endif
            <div class="flex {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
                <div class="flex items-start space-x-2 {{ $message->user_id === auth()->id() ? 'flex-row-reverse space-x-reverse' : '' }}" style="max-width: 70%; min-width: 0;">
                    <div class="flex-shrink-0">
                        @if($message->user->avatar)
                            <img src="{{ Storage::url($message->user->avatar) }}" 
                                alt="{{ $message->user->name }}" 
                                class="w-8 h-8 rounded-full object-cover"
                                loading="lazy"
                                decoding="async">
                        @else
                            <div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white text-xs font-medium">
                                {{ $message->user->initials }}
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-col {{ $message->user_id === auth()->id() ? 'items-end' : 'items-start' }}" style="min-width: 0; flex: 1;">
                        <div class="px-4 py-2 rounded-lg {{ $message->user_id === auth()->id() ? 'bg-gray-900 text-white' : 'bg-white border border-gray-200 text-gray-900' }}" style="max-width: 100%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; min-width: 0;">
                            <p class="text-sm" style="word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: pre-wrap; margin: 0;">{{ $message->message }}</p>
                        </div>
                        <div class="flex items-center gap-1 mt-1">
                            <p class="text-xs text-gray-500">{{ $message->user->name }} • {{ $message->created_at->setTimezone('Asia/Jakarta')->format('H:i') }}</p>
                            @if($message->user_id === auth()->id() && $message->receiver_id)
                                @php
                                    $isRead = isset($message->is_read) ? $message->is_read : $message->isReadBy($message->receiver_id);
                                @endphp
                                @if($isRead)
                                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" style="transform: translateX(2px);"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                                    </svg>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Typing Indicator -->
    <div id="typing-indicator" class="px-4 py-2 hidden">
        <p class="text-sm text-gray-500 italic">
            <span id="typing-user"></span> is typing...
        </p>
    </div>

    <!-- Input Area -->
    <div id="message-input-area" class="border-t border-gray-200 bg-white p-4 flex-shrink-0 w-full" style="position: relative; z-index: 20;">
        <form id="message-form" class="flex space-x-2">
            <input type="hidden" id="group-id" value="{{ $groupId ?? (isset($chatType) && $chatType === 'group' ? '1' : '') }}">
            <input type="hidden" id="receiver-id" value="{{ $receiverId ?? '' }}">
            <input type="text" 
                id="message-input" 
                placeholder="Type a message..." 
                class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent"
                autocomplete="off">
            <button type="submit" 
                class="px-4 sm:px-6 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 whitespace-nowrap">
                Send
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesContainer = document.getElementById('messages');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const typingIndicator = document.getElementById('typing-indicator');
    const typingUser = document.getElementById('typing-user');
    const groupId = document.getElementById('group-id').value;
    // Get receiver_id from hidden input (set from session)
    const receiverId = document.getElementById('receiver-id').value;
    
    let typingTimeout;
    let isTyping = false;
    let lastMessageId = {{ $messages->last() ? $messages->last()->id : 0 }};
    let firstMessageId = {{ $messages->first() ? $messages->first()->id : 0 }};
    let firstMessageCreatedAt = '{{ $messages->first() ? $messages->first()->created_at->toISOString() : "" }}';
    let pollInterval = null;
    let currentChannel = null;
    let isLoadingOlderMessages = false;
    // Check if there are more messages to load (from server)
    let hasMoreOlderMessages = {{ isset($hasMoreOlderMessages) && $hasMoreOlderMessages ? 'true' : 'false' }};
    let isUserScrollingUp = false;
    let lastScrollTop = 0;
    let lastMessageDate = '{{ $messages->last() ? $messages->last()->created_at->setTimezone("Asia/Jakarta")->format("Y-m-d") : "" }}';

    // Debug: Log initial state
    console.log('Chat initialized:', { 
        firstMessageId, 
        lastMessageId, 
        messageCount: {{ $messages->count() }},
        hasMoreOlderMessages 
    });

    // Scroll to bottom on load
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
    lastScrollTop = messagesContainer.scrollTop;
    
    function loadOlderMessages() {
        console.log('loadOlderMessages called with state:', { isLoadingOlderMessages, hasMoreOlderMessages, firstMessageId });
        if (isLoadingOlderMessages || !hasMoreOlderMessages || !firstMessageId) {
            console.log('loadOlderMessages SKIPPED:', { isLoadingOlderMessages, hasMoreOlderMessages, firstMessageId });
            return;
        }
        
        console.log('✅ Starting to load older messages, before_id:', firstMessageId);
        isLoadingOlderMessages = true;
        const loadingIndicator = document.getElementById('loading-older-messages');
        if (loadingIndicator) {
            loadingIndicator.classList.remove('hidden');
        }
        
        // Save current scroll position and height
        const oldScrollHeight = messagesContainer.scrollHeight;
        const oldScrollTop = messagesContainer.scrollTop;
        
        const params = new URLSearchParams({
            before_id: firstMessageId
        });
        if (firstMessageCreatedAt) {
            params.append('before_created_at', firstMessageCreatedAt);
        }
        if (receiverId) {
            params.append('receiver_id', receiverId);
        } else {
            params.append('group_id', groupId || '1');
        }
        
        fetch('{{ route('chat.load-older') }}?' + params.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            console.log('Received older messages:', data.messages ? data.messages.length : 0, data);
            if (data.messages && data.messages.length > 0) {
                // Find where to insert (before first existing message, skip loading indicator)
                const loadingIndicator = document.getElementById('loading-older-messages');
                
                // Find first message element (skip loading indicator)
                const allChildren = Array.from(messagesContainer.children);
                let insertBeforeElement = allChildren.find(child => 
                    child.hasAttribute('data-message-id') && child.id !== 'loading-older-messages'
                );
                
                // If still not found, try querySelector
                if (!insertBeforeElement) {
                    insertBeforeElement = messagesContainer.querySelector('[data-message-id]');
                }
                
                console.log('Found insertBeforeElement:', insertBeforeElement, 'All children count:', allChildren.length);
                
                // Create fragment for batch insert
                const fragment = document.createDocumentFragment();
                
                // Track dates for separator insertion
                let prevDate = null;
                
                // Get the first existing message's date to avoid duplicate separator
                let firstExistingDate = null;
                if (insertBeforeElement) {
                    // Find the date separator or message before insertBeforeElement to get its date
                    const previousElement = insertBeforeElement.previousElementSibling;
                    if (previousElement && previousElement.hasAttribute('data-date-separator')) {
                        firstExistingDate = previousElement.getAttribute('data-date-separator');
                    } else if (previousElement && previousElement.hasAttribute('data-message-id')) {
                        // Get date from message by finding its date separator
                        let current = previousElement.previousElementSibling;
                        while (current) {
                            if (current.hasAttribute('data-date-separator')) {
                                firstExistingDate = current.getAttribute('data-date-separator');
                                break;
                            }
                            if (current.hasAttribute('data-message-id')) {
                                current = current.previousElementSibling;
                            } else {
                                break;
                            }
                        }
                    }
                }
                
                // Messages from server are already in correct order: [oldest, ..., newest] (after reverse in controller)
                // We need to insert them so oldest appears first (on top)
                // Since we're inserting before the first element, we append to fragment in order
                data.messages.forEach((msg, index) => {
                    // Skip if message already exists
                    const existing = messagesContainer.querySelector(`[data-message-id="${msg.id}"]`);
                    if (existing) {
                        console.log('⚠️ Message already exists, skipping:', msg.id);
                        return;
                    }
                    
                    const msgDate = getDateString(msg.created_at);
                    
                    // Add date separator if date changed and not duplicate with existing messages
                    if ((prevDate === null || prevDate !== msgDate) && msgDate !== firstExistingDate) {
                        const dateSeparator = createDateSeparator(msgDate);
                        fragment.appendChild(dateSeparator);
                        prevDate = msgDate;
                    } else if (prevDate === null) {
                        // First message in batch
                        prevDate = msgDate;
                    }
                    
                    const messageDiv = createMessageElement(msg);
                    if (messageDiv) {
                        fragment.appendChild(messageDiv);
                    }
                });
                
                // Only insert if fragment has children
                if (fragment.children.length > 0) {
                    console.log('📝 About to insert', fragment.children.length, 'messages. insertBeforeElement:', insertBeforeElement);
                    // Insert all messages at once before the first existing message
                    if (insertBeforeElement) {
                        messagesContainer.insertBefore(fragment, insertBeforeElement);
                        console.log('✅ Successfully inserted', fragment.children.length, 'messages before first message. New total children:', messagesContainer.children.length);
                    } else {
                        // If no existing messages found, insert at the beginning (after loading indicator if exists)
                        if (loadingIndicator) {
                            if (loadingIndicator.nextSibling) {
                                messagesContainer.insertBefore(fragment, loadingIndicator.nextSibling);
                            } else {
                                // Insert after loading indicator (it will be the last child)
                                messagesContainer.appendChild(fragment);
                            }
                        } else {
                            // No loading indicator, insert at start
                            messagesContainer.insertBefore(fragment, messagesContainer.firstChild);
                        }
                        console.log('✅ Successfully inserted', fragment.children.length, 'messages at start. New total children:', messagesContainer.children.length);
                    }
                    
                    // Update firstMessageId and firstMessageCreatedAt to the oldest loaded message (first in array)
                    if (data.messages.length > 0) {
                        const oldestMessage = data.messages[0];
                        if (oldestMessage && oldestMessage.id) {
                            firstMessageId = oldestMessage.id;
                            if (oldestMessage.created_at) {
                                firstMessageCreatedAt = oldestMessage.created_at;
                            }
                            console.log('✅ Updated firstMessageId to:', firstMessageId, 'created_at:', firstMessageCreatedAt);
                        }
                    }
                    
                    // Restore scroll position after messages are added (maintain user's scroll position)
                    requestAnimationFrame(() => {
                        const newScrollHeight = messagesContainer.scrollHeight;
                        const scrollDiff = newScrollHeight - oldScrollHeight;
                        messagesContainer.scrollTop = oldScrollTop + scrollDiff;
                        console.log('📐 Scroll position restored:', { 
                            oldHeight: oldScrollHeight, 
                            newHeight: newScrollHeight, 
                            scrollDiff,
                            oldScrollTop: oldScrollTop,
                            newScrollTop: messagesContainer.scrollTop,
                            currentScrollTop: messagesContainer.scrollTop
                        });
                    });
                } else {
                    console.log('⚠️ No new messages to insert (all duplicates)');
                }
                
                // Update hasMoreOlderMessages from server response
                if (data.has_more !== undefined) {
                    hasMoreOlderMessages = data.has_more;
                    console.log('🔄 hasMoreOlderMessages updated from server:', hasMoreOlderMessages);
                } else {
                    // Fallback: if we got less than 40 messages, we've reached the end
                    if (data.messages.length < 40) {
                        hasMoreOlderMessages = false;
                        console.log('🛑 hasMoreOlderMessages set to false (got less than 40 messages)');
                    }
                }
            } else {
                hasMoreOlderMessages = false;
                console.log('No messages returned, reached the end');
            }
        })
        .catch((error) => {
            console.error('Error loading older messages:', error);
        })
        .finally(() => {
            isLoadingOlderMessages = false;
            if (loadingIndicator) {
                loadingIndicator.classList.add('hidden');
            }
        });
    }

    // Mark messages as read when viewing chat (real-time)
    function markMessagesAsRead() {
        if (!receiverId && !groupId) {
            // Default group chat
            const defaultGroupId = '1';
            const messageIds = Array.from(document.querySelectorAll('[data-message-id]'))
                .map(el => parseInt(el.getAttribute('data-message-id')))
                .filter(id => id > 0);
            
            if (messageIds.length > 0) {
                fetch('{{ route('chat.mark-read') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message_ids: messageIds })
                })
                .then(() => {
                    // Update unread count immediately
                    if (typeof window.updateUnreadCount === 'function') {
                        window.updateUnreadCount();
                    }
                })
                .catch(() => {});
            }
        } else if (receiverId) {
            // Private chat - mark messages from receiver as read
            // Only mark messages where receiver_id is current user (messages sent TO us)
            const messageIds = Array.from(document.querySelectorAll('[data-message-id]'))
                .map(el => {
                    const msgDiv = el;
                    // Check if message is on left side (from receiver) not right side (from us)
                    const isFromReceiver = !msgDiv.classList.contains('justify-end');
                    const msgId = parseInt(msgDiv.getAttribute('data-message-id'));
                    // Only mark messages from receiver (left side messages, not our own)
                    if (isFromReceiver && msgId > 0) {
                        return msgId;
                    }
                    return null;
                })
                .filter(id => id !== null && id > 0);
            
            if (messageIds.length > 0) {
                fetch('{{ route('chat.mark-read') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message_ids: messageIds })
                })
                .then(() => {
                    // Update unread count immediately
                    if (typeof window.updateUnreadCount === 'function') {
                        window.updateUnreadCount();
                    }
                })
                .catch(() => {});
            }
        } else if (groupId) {
            // Group chat - mark all messages as read
            const messageIds = Array.from(document.querySelectorAll('[data-message-id]'))
                .map(el => parseInt(el.getAttribute('data-message-id')))
                .filter(id => id > 0);
            
            if (messageIds.length > 0) {
                fetch('{{ route('chat.mark-read') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message_ids: messageIds })
                })
                .then(() => {
                    // Update unread count immediately
                    if (typeof window.updateUnreadCount === 'function') {
                        window.updateUnreadCount();
                    }
                })
                .catch(() => {});
            }
        }
    }

    // Mark messages as read on page load and when new messages arrive
    setTimeout(markMessagesAsRead, 500);
    
    // Also mark as read when scrolling to bottom (user is viewing messages)
    let scrollTimeout;
    let scrollCheckTimeout;
    messagesContainer.addEventListener('scroll', function() {
        const currentScrollTop = messagesContainer.scrollTop;
        const scrollHeight = messagesContainer.scrollHeight;
        const clientHeight = messagesContainer.clientHeight;
        
        // Detect if user is scrolling up
        isUserScrollingUp = currentScrollTop < lastScrollTop;
        lastScrollTop = currentScrollTop;
        
        // Check if scrolled near top for loading older messages (within 300px from top)
        // Use debounce to avoid multiple calls
        clearTimeout(scrollCheckTimeout);
        scrollCheckTimeout = setTimeout(() => {
            if (currentScrollTop < 300 && !isLoadingOlderMessages && hasMoreOlderMessages && firstMessageId) {
                console.log('🔄 Scroll near top detected! Triggering loadOlderMessages...', { 
                    scrollTop: currentScrollTop, 
                    firstMessageId,
                    firstMessageCreatedAt,
                    hasMoreOlderMessages,
                    isLoadingOlderMessages,
                    isUserScrollingUp
                });
                loadOlderMessages();
            } else if (currentScrollTop < 300) {
                console.log('⚠️ Scroll near top but conditions NOT met:', {
                    scrollTop: currentScrollTop,
                    isLoadingOlderMessages,
                    hasMoreOlderMessages,
                    firstMessageId,
                    firstMessageCreatedAt
                });
            }
        }, 100);
        
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            // Only mark as read if user is near bottom (not scrolling up)
            if (currentScrollTop + clientHeight >= scrollHeight - 100) {
                markMessagesAsRead();
            }
        }, 500);
    });

    // Listen for private messages received via user channel (from layout)
    window.addEventListener('privateMessageReceived', function(e) {
        if (e.detail && e.detail.message) {
            const msg = e.detail.message;
            // Only add if this is the current chat being viewed
            if (receiverId && parseInt(receiverId) === parseInt(msg.user_id)) {
                const existingMsg = document.querySelector(`[data-message-id="${msg.id}"]`);
                if (!existingMsg) {
                    addMessage(msg);
                    if (msg.id > lastMessageId) {
                        lastMessageId = msg.id;
                    }
                    // Only scroll to bottom if user is not scrolling up
                    if (!isUserScrollingUp) {
                        const isNearBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 200;
                        if (isNearBottom) {
                            requestAnimationFrame(() => {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            });
                        }
                    }
                    // Update unread count
                    setTimeout(window.updateUnreadCount, 500);
                }
            }
        }
    });

    // Get channel name based on chat type
    function getChannelName() {
        if (receiverId) {
            // Private chat channel
            const userId = {{ auth()->id() }};
            const otherUserId = parseInt(receiverId);
            return 'chat.private.' + Math.min(userId, otherUserId) + '.' + Math.max(userId, otherUserId);
        } else if (groupId) {
            // Group chat channel
            return 'chat.group.' + groupId;
        }
        // Default group chat (Grup Anime)
        return 'chat.group.1';
    }

    // Polling function - more aggressive for real-time
    function pollForMessages() {
        const params = new URLSearchParams({
            last_id: lastMessageId
        });
        if (receiverId) {
            params.append('receiver_id', receiverId);
        } else {
            // Default to group chat (Grup Anime = id 1)
            params.append('group_id', groupId || '1');
        }
        
        // Add timestamp to prevent caching
        params.append('_t', Date.now());
        
        fetch('{{ route('chat.poll') }}?' + params.toString(), {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            },
            cache: 'no-store'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.messages && data.messages.length > 0) {
                let hasNewMessage = false;
                data.messages.forEach(msg => {
                    // Check if message is not already displayed
                    const existingMessage = document.querySelector(`[data-message-id="${msg.id}"]`);
                    if (!existingMessage) {
                        addMessage(msg);
                        if (msg.id > lastMessageId) {
                            lastMessageId = msg.id;
                            hasNewMessage = true;
                        }
                        // Update unread count if message is from other user in private chat
                        if (receiverId && msg.user_id !== {{ auth()->id() }}) {
                            // Mark as read when polling (user is viewing chat)
                            setTimeout(() => {
                                markMessagesAsRead();
                            }, 300);
                            setTimeout(window.updateUnreadCount, 500);
                        }
                        
                        // Update check mark for our own messages if read status changed
                        if (receiverId && msg.user_id === {{ auth()->id() }} && msg.is_read !== undefined) {
                            if (typeof window.updateCheckMark === 'function') {
                                window.updateCheckMark(msg.id, msg.is_read);
                            }
                        }
                    } else {
                        // Message already exists - update check mark if read status changed
                        if (receiverId && msg.user_id === {{ auth()->id() }} && msg.is_read !== undefined) {
                            if (typeof window.updateCheckMark === 'function') {
                                window.updateCheckMark(msg.id, msg.is_read);
                            }
                        }
                    }
                });
                // Only scroll to bottom if new messages were added AND user is not scrolling up
                if (hasNewMessage && !isUserScrollingUp) {
                    // Check if user is near bottom before auto-scrolling
                    const isNearBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 200;
                    if (isNearBottom) {
                        requestAnimationFrame(() => {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        });
                    }
                }
            }
        })
        .catch(() => {
            // Silently handle errors
        });
    }

    // Start polling immediately
    pollForMessages();
    // Use 3 second interval for better performance (will be reduced when WebSocket connects)
    pollInterval = setInterval(pollForMessages, 3000);

        // Initialize Echo if available
        if (typeof window.Echo !== 'undefined') {
            try {
            const channelName = getChannelName();
            currentChannel = window.Echo.channel(channelName);
            
            currentChannel
                .listen('.message.sent', (e) => {
                    if (e.message) {
                        // Check if message belongs to current chat
                        const msgGroupId = e.message.group_id ? e.message.group_id.toString() : null;
                        const msgReceiverId = e.message.receiver_id ? e.message.receiver_id.toString() : null;
                        const currentGroupId = groupId ? groupId.toString() : null;
                        const currentReceiverId = receiverId ? receiverId.toString() : null;
                        
                        // For private chat
                        if (receiverId) {
                            const currentUserId = {{ auth()->id() }};
                            const msgUserId = parseInt(e.message.user_id);
                            const msgReceiverId = e.message.receiver_id ? parseInt(e.message.receiver_id) : null;
                            const currentReceiverIdNum = parseInt(receiverId);
                            
                            // Check if this message is part of this private conversation
                            // Message is valid if:
                            // 1. Sent by other user TO current user (receiver sees message from sender)
                            // 2. Sent by current user TO other user (sender sees their own message)
                            const isFromOtherUserToMe = msgUserId === currentReceiverIdNum && msgReceiverId === currentUserId;
                            const isFromMeToOther = msgUserId === currentUserId && msgReceiverId === currentReceiverIdNum;
                            
                            // Accept message if it's part of this conversation
                            if (isFromOtherUserToMe || isFromMeToOther) {
                                const existingMsg = document.querySelector(`[data-message-id="${e.message.id}"]`);
                                if (!existingMsg) {
                                    addMessage(e.message);
                                    if (e.message.id > lastMessageId) {
                                        lastMessageId = e.message.id;
                                    }
                                    // Scroll to bottom when receiving message
                                    requestAnimationFrame(() => {
                                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                    });
                                    if (isFromOtherUserToMe) {
                                        // Mark as read immediately when receiving message (user is viewing chat)
                                        setTimeout(() => {
                                            markMessagesAsRead();
                                        }, 300);
                                        // Update unread count when receiving message from other user
                                        setTimeout(window.updateUnreadCount, 500);
                                    }
                                } else {
                                    // Update check mark if message already exists (read status changed)
                                    if (isFromMeToOther && e.message.is_read !== undefined) {
                                        window.updateCheckMark(e.message.id, e.message.is_read);
                                    }
                                }
                            }
                        } else if (groupId && msgGroupId === currentGroupId) {
                            // Group message
                            const existingMsg = document.querySelector(`[data-message-id="${e.message.id}"]`);
                            if (!existingMsg) {
                                addMessage(e.message);
                                if (e.message.id > lastMessageId) {
                                    lastMessageId = e.message.id;
                                }
                                // Scroll to bottom when receiving message
                                requestAnimationFrame(() => {
                                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                });
                            }
                        } else if (!receiverId) {
                            // Group chat (default or specific group)
                            const isDefaultGroup = !groupId && msgGroupId === '1';
                            const isSameGroup = groupId && msgGroupId === currentGroupId;
                            
                            if (isDefaultGroup || isSameGroup) {
                                const existingMsg = document.querySelector(`[data-message-id="${e.message.id}"]`);
                                if (!existingMsg) {
                                    addMessage(e.message);
                                    if (e.message.id > lastMessageId) {
                                        lastMessageId = e.message.id;
                                    }
                                    // Scroll to bottom when receiving message
                                    requestAnimationFrame(() => {
                                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                    });
                                }
                            }
                        }
                    }
                });
            
            currentChannel.subscribed(() => {
                // Keep polling as backup but less frequent when WebSocket is connected
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = setInterval(pollForMessages, 8000); // Poll every 8 seconds as backup
                }
            });
            
            // Also listen for connection errors
            currentChannel.error((error) => {
                // If WebSocket fails, increase polling frequency
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = setInterval(pollForMessages, 3000); // Poll every 3 seconds if WebSocket fails
                }
            });
            
            // If subscription fails, ensure polling continues
            setTimeout(() => {
                if (!currentChannel || !currentChannel.subscribed) {
                    // If not subscribed after 3 seconds, rely on polling
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = setInterval(pollForMessages, 3000);
                    }
                }
            }, 3000);

            // Typing indicator
            currentChannel
                .listen('.typing', (e) => {
                    if (e.user && e.user.id !== {{ auth()->id() }}) {
                        typingUser.textContent = e.user.name;
                        typingIndicator.classList.remove('hidden');

                        clearTimeout(typingTimeout);
                        typingTimeout = setTimeout(() => {
                            typingIndicator.classList.add('hidden');
                        }, 3000);
                    }
                });
        } catch (error) {
            // Silently handle Echo initialization errors
        }
    }

    // Send message
    messageForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const message = messageInput.value.trim();
        if (!message) return;

        const payload = {
            message: message
        };
        // Set group_id or receiver_id
        if (receiverId) {
            payload.receiver_id = receiverId;
        } else {
            // Default to group chat (Grup Anime = id 1)
            payload.group_id = groupId || '1';
        }

        fetch('{{ route('chat.send') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.message) {
                messageInput.value = '';
                stopTyping();
                // Add message immediately for sender (don't wait for WebSocket or polling)
                const messageId = data.message.id;
                const existingMessage = document.querySelector(`[data-message-id="${messageId}"]`);
                if (!existingMessage) {
                    // Add message immediately
                    addMessage(data.message);
                    lastMessageId = Math.max(lastMessageId, messageId);
                    
                    // Only scroll to bottom if user is not scrolling up
                    if (!isUserScrollingUp) {
                        const isNearBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 200;
                        if (isNearBottom) {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            setTimeout(() => {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                            }, 100);
                        }
                    }
                    
                    // If private chat, update sidebar immediately with chat data from response
                    if (receiverId && data.chatData && typeof window.updateSidebarChat === 'function') {
                        // Update sidebar immediately (especially important for first message)
                        window.updateSidebarChat(data.chatData);
                    }
                    
                    // Update unread count after sending message
                    setTimeout(() => {
                        window.updateUnreadCount();
                    }, 300);
                }
            } else {
                alert('Failed to send message. Please try again.');
            }
        })
        .catch(() => {
            alert('Failed to send message. Please try again.');
        });
    });

    // Typing indicator - with aggressive debounce
    let typingDebounceTimeout;
    messageInput.addEventListener('input', function() {
        // Debounce typing event - only send after 1.5 seconds of no typing
        clearTimeout(typingDebounceTimeout);
        typingDebounceTimeout = setTimeout(() => {
            if (!isTyping) {
                isTyping = true;
                sendTypingEvent();
            }
        }, 1500);

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            stopTyping();
        }, 2000);
    });

    function sendTypingEvent() {
        const payload = {};
        // Set group_id or receiver_id
        if (receiverId) {
            payload.receiver_id = receiverId;
        } else {
            // Default to group chat (Grup Anime = id 1)
            payload.group_id = groupId || '1';
        }

        fetch('{{ route('chat.typing') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        });
    }

    function stopTyping() {
        isTyping = false;
    }

    function createMessageElement(message) {
        if (!message || !message.id) {
            return null;
        }
        
        const isOwnMessage = message.user_id === {{ auth()->id() }};
        const isPrivateMessage = message.receiver_id && !message.group_id;
        const messageDiv = document.createElement('div');
        messageDiv.className = `flex ${isOwnMessage ? 'justify-end' : 'justify-start'}`;
        messageDiv.setAttribute('data-message-id', message.id);

        const avatarHtml = message.user.avatar
            ? `<img src="/storage/${message.user.avatar}" alt="${escapeHtml(message.user.name)}" class="w-8 h-8 rounded-full object-cover" loading="lazy" decoding="async">`
            : `<div class="w-8 h-8 rounded-full bg-gray-600 flex items-center justify-center text-white text-xs font-medium">${escapeHtml(message.user.initials || message.user.name.charAt(0).toUpperCase())}</div>`;

        // Check mark for private messages sent by current user
        let checkMarkHtml = '';
        if (isOwnMessage && isPrivateMessage) {
            const isRead = message.is_read === true || message.is_read === 1;
            if (isRead) {
                // Double check (blue) - sudah dibaca
                checkMarkHtml = `
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" style="transform: translateX(2px);"/>
                    </svg>
                `;
            } else {
                // Single check (gray) - belum dibaca
                checkMarkHtml = `
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                    </svg>
                `;
            }
        }

        messageDiv.innerHTML = `
                <div class="flex items-start space-x-2 ${isOwnMessage ? 'flex-row-reverse space-x-reverse' : ''}" style="max-width: 70%; min-width: 0;">
                <div class="flex-shrink-0">
                    ${avatarHtml}
                </div>
                <div class="flex flex-col ${isOwnMessage ? 'items-end' : 'items-start'}" style="min-width: 0; flex: 1;">
                    <div class="px-4 py-2 rounded-lg ${isOwnMessage ? 'bg-gray-900 text-white' : 'bg-white border border-gray-200 text-gray-900'}" style="max-width: 100%; word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; min-width: 0;">
                        <p class="text-sm" style="word-wrap: break-word; overflow-wrap: break-word; word-break: break-word; white-space: pre-wrap; margin: 0;">${escapeHtml(message.message)}</p>
                    </div>
                    <div class="flex items-center gap-1 mt-1">
                        <p class="text-xs text-gray-500">${escapeHtml(message.user.name)} • ${formatTime(message.created_at)}</p>
                        ${checkMarkHtml}
                    </div>
                </div>
            </div>
        `;

        return messageDiv;
    }

    function addMessage(message) {
        if (!message || !message.id) {
            return;
        }
        
        // Check if message already exists to prevent duplicates
        const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
        if (existingMessage) {
            return; // Message already displayed
        }
        
        const messageDiv = createMessageElement(message);
        if (!messageDiv) {
            return;
        }
        
        // Check if we need to add date separator
        const messageDate = getDateString(message.created_at);
        if (lastMessageDate && lastMessageDate !== messageDate) {
            // Date changed, add separator
            const dateSeparator = createDateSeparator(messageDate);
            messagesContainer.appendChild(dateSeparator);
        } else if (!lastMessageDate) {
            // First message, add date separator
            const dateSeparator = createDateSeparator(messageDate);
            messagesContainer.appendChild(dateSeparator);
        }
        
        messagesContainer.appendChild(messageDiv);
        lastMessageDate = messageDate; // Update last message date
        
        // Only scroll to bottom if user is not scrolling up
        if (!isUserScrollingUp) {
            const isNearBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 200;
            if (isNearBottom) {
                requestAnimationFrame(() => {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                });
            }
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        // Format to Jakarta timezone (HH:mm format)
        const options = { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', hour12: false };
        return date.toLocaleTimeString('id-ID', options);
    }

    function formatDateLabel(dateString) {
        // dateString is in format YYYY-MM-DD
        const today = new Date();
        const todayJakarta = new Date(today.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
        const todayStr = todayJakarta.getFullYear() + '-' + 
                        String(todayJakarta.getMonth() + 1).padStart(2, '0') + '-' + 
                        String(todayJakarta.getDate()).padStart(2, '0');
        
        const yesterdayJakarta = new Date(todayJakarta);
        yesterdayJakarta.setDate(yesterdayJakarta.getDate() - 1);
        const yesterdayStr = yesterdayJakarta.getFullYear() + '-' + 
                            String(yesterdayJakarta.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(yesterdayJakarta.getDate()).padStart(2, '0');
        
        if (dateString === todayStr) {
            return 'Hari ini';
        } else if (dateString === yesterdayStr) {
            return 'Kemarin';
        } else {
            // Format: Senin, 23 Desember 2024
            // Parse YYYY-MM-DD and create date in Jakarta timezone
            const [year, month, day] = dateString.split('-').map(Number);
            const date = new Date(year, month - 1, day);
            
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            
            const dayName = days[date.getDay()];
            const monthName = months[date.getMonth()];
            
            return `${dayName}, ${day} ${monthName} ${year}`;
        }
    }

    function getDateString(timestamp) {
        const date = new Date(timestamp);
        // Convert to Jakarta timezone and get date string (YYYY-MM-DD)
        const jakartaDate = new Date(date.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' }));
        const year = jakartaDate.getFullYear();
        const month = String(jakartaDate.getMonth() + 1).padStart(2, '0');
        const day = String(jakartaDate.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function createDateSeparator(dateString) {
        const separator = document.createElement('div');
        separator.className = 'flex justify-center my-4';
        separator.setAttribute('data-date-separator', dateString);
        separator.innerHTML = `
            <div class="px-3 py-1 bg-gray-200 rounded-full">
                <p class="text-xs text-gray-600 font-medium">${formatDateLabel(dateString)}</p>
            </div>
        `;
        return separator;
    }

    // Function to update check mark for private messages - make it globally accessible
    window.updateCheckMark = function(messageId, isRead) {
        const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
        if (!messageDiv) return;
        
        const checkMarkContainer = messageDiv.querySelector('.flex.items-center.gap-1');
        if (!checkMarkContainer) return;
        
        // Remove existing check mark
        const existingCheck = checkMarkContainer.querySelector('svg');
        if (existingCheck) {
            existingCheck.remove();
        }
        
        // Add new check mark based on read status
        if (isRead) {
            // Double check (blue) - sudah dibaca
            checkMarkContainer.innerHTML += `
                <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" style="transform: translateX(2px);"/>
                </svg>
            `;
        } else {
            // Single check (gray) - belum dibaca
            checkMarkContainer.innerHTML += `
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
                </svg>
            `;
        }
    };

    // Update unread count in sidebar - make it globally accessible
    window.updateUnreadCount = function() {
        fetch('{{ route('chat.unread-count') }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Update unread counts for private chats
            if (data.unread_counts) {
                Object.keys(data.unread_counts).forEach(userId => {
                    const count = data.unread_counts[userId];
                    const chatItem = document.querySelector(`[data-chat-user-id="${userId}"]`);
                    if (chatItem) {
                        // Find the name container
                        const nameContainer = chatItem.querySelector('.flex.items-center.justify-between');
                        if (nameContainer) {
                            let badge = nameContainer.querySelector('.unread-badge');
                            if (count > 0) {
                                if (!badge) {
                                    badge = document.createElement('span');
                                    badge.className = 'unread-badge ml-2 flex-shrink-0 bg-gray-900 text-white text-xs font-medium rounded-full px-2 py-0.5 min-w-[20px] text-center';
                                    nameContainer.appendChild(badge);
                                }
                                badge.textContent = count > 99 ? '99+' : count.toString();
                                badge.style.display = 'block';
                            } else if (badge) {
                                badge.remove();
                            }
                        }
                    }
                });
            }
            
            // Update unread counts for group chats
            if (data.group_unread_counts) {
                Object.keys(data.group_unread_counts).forEach(groupId => {
                    const count = data.group_unread_counts[groupId];
                    // Default group chat (Grup Anime = id 1)
                    if (groupId == '1' || groupId == 1) {
                        const groupChatItem = document.getElementById('group-chat-item');
                        if (groupChatItem) {
                            const nameContainer = groupChatItem.querySelector('.flex.items-center.justify-between');
                            if (nameContainer) {
                                let badge = nameContainer.querySelector('#group-unread-badge');
                                if (!badge) {
                                    badge = document.getElementById('group-unread-badge');
                                }
                                if (count > 0) {
                                    if (badge) {
                                        badge.textContent = count > 99 ? '99+' : count.toString();
                                        badge.classList.remove('hidden');
                                    } else {
                                        badge = document.createElement('span');
                                        badge.id = 'group-unread-badge';
                                        badge.className = 'group-unread-badge ml-2 flex-shrink-0 bg-gray-900 text-white text-xs font-medium rounded-full px-2 py-0.5 min-w-[20px] text-center';
                                        badge.textContent = count > 99 ? '99+' : count.toString();
                                        nameContainer.appendChild(badge);
                                    }
                                } else {
                                    if (badge) {
                                        badge.classList.add('hidden');
                                    }
                                }
                            }
                        }
                    }
                });
            }
        })
        .catch(() => {
            // Silently handle errors
        });
    };

    // Combined polling function for unread count and read status (optimized)
    let readStatusPollInterval = null;
    function combinedPoll() {
        // Update unread count
        if (typeof window.updateUnreadCount === 'function') {
            window.updateUnreadCount();
        }
        
        // Poll for read status updates for our own messages in private chat (centang langsung biru)
        if (receiverId) {
            const ourMessageIds = Array.from(document.querySelectorAll('[data-message-id]'))
                .map(el => {
                    const msgDiv = el;
                    // Check if message is from us (right side)
                    const isFromUs = msgDiv.classList.contains('justify-end');
                    const msgId = parseInt(msgDiv.getAttribute('data-message-id'));
                    if (isFromUs && msgId > 0) {
                        return msgId;
                    }
                    return null;
                })
                .filter(id => id !== null && id > 0);
            
            if (ourMessageIds.length > 0) {
                // Check read status via dedicated endpoint
                fetch('{{ route('chat.check-read-status') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ message_ids: ourMessageIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.messages && Array.isArray(data.messages)) {
                        data.messages.forEach(msg => {
                            // Update centang if read status changed
                            if (typeof window.updateCheckMark === 'function') {
                                window.updateCheckMark(msg.id, msg.is_read);
                            }
                        });
                    }
                })
                .catch(() => {});
            }
        }
    }
    
    // Combined polling every 3 seconds (optimized - reduced from multiple separate polls)
    readStatusPollInterval = setInterval(combinedPoll, 3000);
    
    // Initial poll
    setTimeout(combinedPoll, 1000);

    // Cleanup on page unload (optimized - cleanup all intervals)
    window.addEventListener('beforeunload', function() {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
        if (readStatusPollInterval) {
            clearInterval(readStatusPollInterval);
        }
        if (currentChannel) {
            window.Echo.leave(currentChannel.name);
        }
        // Clear all timeouts
        if (typingTimeout) clearTimeout(typingTimeout);
        if (typingDebounceTimeout) clearTimeout(typingDebounceTimeout);
        if (scrollTimeout) clearTimeout(scrollTimeout);
    });
    
    // Also cleanup when page is hidden (tab switch)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden - pause polling
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }
            if (readStatusPollInterval) {
                clearInterval(readStatusPollInterval);
                readStatusPollInterval = null;
            }
        } else {
            // Page is visible - resume polling
            if (!pollInterval) {
                pollInterval = setInterval(pollForMessages, 3000);
            }
            if (!readStatusPollInterval) {
                readStatusPollInterval = setInterval(combinedPoll, 3000);
            }
        }
    });
});
</script>
@endsection
