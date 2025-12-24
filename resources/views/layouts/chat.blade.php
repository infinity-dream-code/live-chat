<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Chat App') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (max-width: 768px) {
            .sidebar-mobile {
                position: fixed;
                left: -100%;
                top: 0;
                bottom: 0;
                width: 80%;
                max-width: 320px;
                transition: left 0.3s ease;
                z-index: 100;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .sidebar-mobile.active {
                left: 0;
            }
            .overlay-mobile {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 90;
            }
            .overlay-mobile.active {
                display: block;
            }
            .mobile-header {
                display: flex;
            }
            /* Ensure input area is always visible on mobile */
            body {
                height: 100vh;
                height: 100dvh; /* Dynamic viewport height for mobile */
                overflow: hidden;
            }
            /* Ensure chat content container uses flexbox correctly */
            .chat-content-wrapper {
                display: flex !important;
                flex-direction: column !important;
                height: 100% !important;
                min-height: 0 !important;
            }
            #messages {
                flex: 1 1 0% !important;
                min-height: 0 !important;
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            /* Ensure input area is always visible, but hide when sidebar is open */
            #message-input-area {
                display: block !important;
                visibility: visible !important;
                position: relative !important;
                z-index: 10 !important;
                background-color: white !important;
                width: 100% !important;
                flex-shrink: 0 !important;
                transition: display 0.3s ease;
            }
            /* Hide input area when sidebar is active - handled by JavaScript */
            #message-input-area.sidebar-hidden {
                display: none !important;
            }
            /* Fix sidebar menu spacing for mobile - add bottom padding to prevent logout from being cut off */
            .sidebar-mobile > div:last-child {
                padding-bottom: 5rem !important;
            }
            @media (min-width: 769px) {
                .sidebar-mobile > div:last-child {
                    padding-bottom: 1rem !important;
                }
            }
        }
        @media (min-width: 769px) {
            .mobile-header {
                display: none;
            }
        }
    </style>
</head>
<body class="bg-gray-50 h-screen overflow-hidden">
    <div class="overlay-mobile" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    
    <div class="flex h-full">
        <div class="w-64 md:w-64 bg-white border-r border-gray-200 flex flex-col h-full sidebar-mobile" id="sidebar">
            <div class="p-4 border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center justify-between mb-3">
                    <h1 class="text-xl font-medium text-gray-900">Chat</h1>
                    <button class="md:hidden p-2 hover:bg-gray-100 rounded-lg" onclick="toggleSidebar()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="relative">
                    <input type="text" 
                        id="search-user-input"
                        placeholder="Search user..." 
                        class="w-full px-3 py-2 pl-9 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500 focus:border-transparent text-sm"
                        autocomplete="off">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <div id="search-results" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-y-auto"></div>
                </div>
            </div>
            
            <div class="p-4 border-b border-gray-200 flex-shrink-0">
                <div class="flex items-center space-x-3">
                    @if(auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}" 
                            alt="{{ auth()->user()->name }}" 
                            class="w-10 h-10 rounded-full object-cover"
                            loading="eager"
                            decoding="async">
                    @else
                        <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-sm">
                            {{ auth()->user()->initials }}
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ '@' . auth()->user()->username }}</p>
                    </div>
                </div>
            </div>

            <div id="chat-users-list" class="flex-1 overflow-y-auto">
                <a href="{{ route('chat') }}" 
                    id="group-chat-item"
                    onclick="clearReceiverAndNavigate(); return false;"
                    class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors {{ !isset($sessionReceiverId) && (!isset($groupId) || $groupId == 1) ? 'bg-gray-50' : '' }}"
                    rel="prefetch">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-sm">
                            GA
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900 truncate">Grup Anime</p>
                                <span id="group-unread-badge" class="group-unread-badge ml-2 flex-shrink-0 bg-gray-900 text-white text-xs font-medium rounded-full px-2 py-0.5 min-w-[20px] text-center hidden">0</span>
                            </div>
                            <p class="text-xs text-gray-500 truncate">Group chat</p>
                        </div>
                    </div>
                </a>

                @if(isset($privateChats) && $privateChats->count() > 0)
                    @foreach($privateChats as $chat)
                        <a href="#" 
                            class="block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors {{ isset($sessionReceiverId) && $sessionReceiverId == $chat['id'] ? 'bg-gray-50' : '' }}"
                            data-chat-user-id="{{ $chat['id'] }}"
                            data-receiver-id="{{ $chat['id'] }}"
                            onclick="setReceiverAndNavigate({{ $chat['id'] }}); return false;"
                            rel="prefetch">
                            <div class="flex items-center space-x-3">
                                @if($chat['avatar'])
                                    <img src="{{ Storage::url($chat['avatar']) }}" 
                                        alt="{{ $chat['name'] }}" 
                                        class="w-10 h-10 rounded-full object-cover">
                                @else
                                    <div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-sm">
                                        {{ $chat['initials'] }}
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $chat['name'] }}</p>
                                        @if($chat['unread_count'] > 0)
                                            <span class="unread-badge ml-2 flex-shrink-0 bg-gray-900 text-white text-xs font-medium rounded-full px-2 py-0.5 min-w-[20px] text-center">
                                                {{ $chat['unread_count'] > 99 ? '99+' : $chat['unread_count'] }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($chat['last_message'])
                                        <p class="text-xs text-gray-500 truncate">{{ \Illuminate\Support\Str::limit($chat['last_message']['message'], 30) }}</p>
                                    @else
                                        <p class="text-xs text-gray-400">No messages yet</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>

            <div class="p-4 border-t border-gray-200 space-y-2 flex-shrink-0 mt-4" style="padding-bottom: 5rem !important;">
                <a href="{{ route('chat') }}" 
                    class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md {{ request()->routeIs('chat') ? 'bg-gray-100 font-medium' : '' }}"
                    rel="prefetch">
                    Chat
                </a>
                <a href="{{ route('settings') }}" 
                    class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md {{ request()->routeIs('settings*') ? 'bg-gray-100 font-medium' : '' }}"
                    rel="prefetch">
                    Settings
                </a>
                <form action="{{ route('logout') }}" method="POST" class="pt-2">
                    @csrf
                    <button type="submit" 
                        class="block w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md">
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <div class="flex-1 flex flex-col w-full min-h-0 overflow-hidden">
            <div class="mobile-header items-center justify-between p-4 bg-white border-b border-gray-200 md:hidden flex-shrink-0">
                <button class="p-2 hover:bg-gray-100 rounded-lg" onclick="toggleSidebar()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h2 class="text-lg font-medium text-gray-900">Chat</h2>
                <div class="w-10"></div>
            </div>
            @yield('content')
        </div>
    </div>

    <script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const messageInputArea = document.getElementById('message-input-area');
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        
        // Hide/show message input area when sidebar is opened/closed on mobile
        if (messageInputArea && window.innerWidth <= 768) {
            if (sidebar.classList.contains('active')) {
                messageInputArea.classList.add('sidebar-hidden');
            } else {
                messageInputArea.classList.remove('sidebar-hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Handle sidebar and input area visibility (moved from above)
        const overlay = document.getElementById('sidebarOverlay');
        const sidebar = document.getElementById('sidebar');
        const messageInputArea = document.getElementById('message-input-area');
        
        if (overlay && sidebar && messageInputArea) {
            overlay.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    messageInputArea.classList.remove('sidebar-hidden');
                }
            });
        }
        
        // Handle window resize - show input if sidebar is closed on resize to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && messageInputArea) {
                messageInputArea.classList.remove('sidebar-hidden');
            }
        });
        
        document.querySelectorAll('a[rel="prefetch"]').forEach(link => {
            link.addEventListener('mouseenter', function() {
                const linkElement = document.createElement('link');
                linkElement.rel = 'prefetch';
                linkElement.href = this.href;
                document.head.appendChild(linkElement);
            }, { once: true });
        });
        
        const searchInput = document.getElementById('search-user-input');
        const searchResults = document.getElementById('search-results');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch('{{ route('chat.search-users') }}?q=' + encodeURIComponent(query), {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.users && data.users.length > 0) {
                        searchResults.innerHTML = data.users.map(user => `
                            <div class="px-4 py-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 search-user-item" 
                                 data-user-id="${user.id}">
                                <div class="flex items-center space-x-3">
                                    ${user.avatar 
                                        ? `<img src="/storage/${user.avatar}" alt="${user.name}" class="w-10 h-10 rounded-full object-cover" loading="lazy" decoding="async">`
                                        : `<div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-sm">${user.initials}</div>`
                                    }
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">${user.name}</p>
                                        <p class="text-xs text-gray-500">@${user.username}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        searchResults.classList.remove('hidden');
                    } else {
                        searchResults.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500 text-center">No users found</div>';
                        searchResults.classList.remove('hidden');
                    }
                })
                .catch(error => {});
            }, 300);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('hidden');
            }
        });

        document.addEventListener('click', function(e) {
            const searchUserItem = e.target.closest('.search-user-item');
            if (searchUserItem) {
                const userId = searchUserItem.dataset.userId;
                setReceiverAndNavigate(userId);
            }
        });

        if (typeof window.Echo !== 'undefined') {
            const userId = {{ auth()->id() }};
            const userChannel = window.Echo.channel('user.' + userId);
            
            userChannel.subscribed(() => {});
            
            userChannel.listen('.chat.updated', (e) => {
                if (e.chatData && e.chatData.id) {
                    if (typeof window.updateSidebarChat === 'function') {
                        window.updateSidebarChat(e.chatData);
                    }
                    if (typeof window.updateUnreadCount === 'function') {
                        setTimeout(() => {
                            window.updateUnreadCount();
                        }, 300);
                    }
                }
            });
            
            userChannel.listen('.message.read', (e) => {
                if (e.message_id && typeof window.updateCheckMark === 'function') {
                    window.updateCheckMark(e.message_id, true);
                }
            });
            
            userChannel.listen('.message.sent', (e) => {
                if (e.message && e.message.receiver_id) {
                    const msgReceiverId = parseInt(e.message.receiver_id);
                    const msgUserId = parseInt(e.message.user_id);
                    
                    if (msgReceiverId === userId && msgUserId !== userId) {
                        if (e.message.user && typeof window.updateSidebarChat === 'function') {
                            const chatData = {
                                id: msgUserId,
                                name: e.message.user.name || 'User',
                                username: e.message.user.username || '',
                                avatar: e.message.user.avatar || null,
                                initials: e.message.user.initials || (e.message.user.name ? e.message.user.name.charAt(0).toUpperCase() : 'U'),
                                unread_count: 1,
                                last_message: {
                                    message: e.message.message,
                                    created_at: e.message.created_at
                                }
                            };
                            window.updateSidebarChat(chatData);
                        }
                        
                        if (typeof window.updateUnreadCount === 'function') {
                            setTimeout(() => {
                                window.updateUnreadCount();
                            }, 500);
                        }
                        
                        const currentReceiverId = new URLSearchParams(window.location.search).get('receiver_id');
                        if (currentReceiverId && parseInt(currentReceiverId) === msgUserId) {
                            window.dispatchEvent(new CustomEvent('privateMessageReceived', {
                                detail: { message: e.message }
                            }));
                        }
                    }
                }
            });
            
            userChannel.error((error) => {});
        }

        function pollSidebarChats() {
            fetch('{{ route('chat.sidebar-chats') }}', {
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
                if (data.chats && Array.isArray(data.chats)) {
                    updateSidebarFromPoll(data.chats);
                }
            })
            .catch(() => {});
        }

        function updateSidebarFromPoll(chats) {
            const chatUsersList = document.getElementById('chat-users-list');
            if (!chatUsersList) return;

            const existingChatIds = new Set();
            chatUsersList.querySelectorAll('[data-chat-user-id]').forEach(item => {
                existingChatIds.add(parseInt(item.getAttribute('data-chat-user-id')));
            });

            chats.forEach(chat => {
                if (chat.id) {
                    const chatId = parseInt(chat.id);
                    if (existingChatIds.has(chatId)) {
                        const existingChat = chatUsersList.querySelector(`[data-chat-user-id="${chatId}"]`);
                        if (existingChat) {
                            const nameElement = existingChat.querySelector('.text-sm.font-medium');
                            const lastMsgElement = existingChat.querySelector('.text-xs.text-gray-500');
                            if (nameElement && chat.name) {
                                nameElement.textContent = chat.name;
                            }
                            if (lastMsgElement && chat.last_message) {
                                let msgText = '';
                                if (typeof chat.last_message === 'string') {
                                    msgText = chat.last_message;
                                } else if (chat.last_message.message) {
                                    msgText = chat.last_message.message;
                                }
                                if (msgText) {
                                    lastMsgElement.textContent = msgText.substring(0, 30);
                                }
                            }
                            updateUnreadBadge(existingChat, chat.unread_count || 0);
                        }
                    } else {
                        if (typeof window.updateSidebarChat === 'function') {
                            window.updateSidebarChat(chat);
                        }
                    }
                }
            });
        }

        let sidebarPollInterval = setInterval(pollSidebarChats, 5000);
        
        setTimeout(pollSidebarChats, 2000);

        function clearReceiverAndNavigate() {
            fetch('{{ route('chat.set-receiver') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ receiver_id: null })
            })
            .then(() => {
                window.location.href = '{{ route('chat') }}';
            })
            .catch(() => {
                window.location.href = '{{ route('chat') }}';
            });
        }

        window.setReceiverAndNavigate = function(userId) {
            fetch('{{ route('chat.set-receiver') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ receiver_id: parseInt(userId) })
            })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                throw new Error('Failed to set receiver');
            })
            .then(data => {
                if (data.success) {
                    setTimeout(() => {
                        window.location.href = '{{ route('chat.private') }}';
                    }, 100);
                } else {
                    throw new Error('Failed to set receiver');
                }
            })
            .catch(error => {
                console.error('Error setting receiver:', error);
                window.location.href = '{{ route('chat.private') }}';
            });
        };

        window.updateSidebarChat = function(chatData) {
            const chatUsersList = document.getElementById('chat-users-list');
            if (!chatUsersList || !chatData || !chatData.id) return;

            const existingChat = chatUsersList.querySelector(`[data-chat-user-id="${chatData.id}"]`);
            if (existingChat) {
                const nameElement = existingChat.querySelector('.text-sm.font-medium');
                const lastMsgElement = existingChat.querySelector('.text-xs.text-gray-500');
                if (nameElement) nameElement.textContent = chatData.name;
                if (lastMsgElement && chatData.last_message) {
                    const msgText = typeof chatData.last_message === 'string' 
                        ? chatData.last_message 
                        : (chatData.last_message.message || '');
                    lastMsgElement.textContent = msgText.substring(0, 30);
                }
                updateUnreadBadge(existingChat, chatData.unread_count || 0);
                const groupChat = chatUsersList.querySelector('a[href="{{ route('chat') }}"]');
                if (groupChat && groupChat.nextSibling) {
                    chatUsersList.insertBefore(existingChat, groupChat.nextSibling);
                }
            } else {
                const newChatItem = document.createElement('a');
                newChatItem.href = '#';
                newChatItem.className = 'block px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors';
                newChatItem.setAttribute('data-chat-user-id', chatData.id);
                newChatItem.setAttribute('data-receiver-id', chatData.id);
                newChatItem.setAttribute('rel', 'prefetch');
                newChatItem.onclick = function(e) {
                    e.preventDefault();
                    window.setReceiverAndNavigate(chatData.id);
                    return false;
                };
                
                const avatarHtml = chatData.avatar 
                    ? `<img src="/storage/${chatData.avatar}" alt="${chatData.name || ''}" class="w-10 h-10 rounded-full object-cover" loading="lazy" decoding="async">`
                    : `<div class="w-10 h-10 rounded-full bg-gray-600 flex items-center justify-center text-white font-medium text-sm">${chatData.initials || (chatData.name ? chatData.name.charAt(0).toUpperCase() : 'U')}</div>`;
                
                const unreadCount = chatData.unread_count || 0;
                const badgeHtml = unreadCount > 0
                    ? `<span class="unread-badge ml-2 flex-shrink-0 bg-gray-900 text-white text-xs font-medium rounded-full px-2 py-0.5 min-w-[20px] text-center">${unreadCount > 99 ? '99+' : unreadCount}</span>`
                    : '';
                
                let lastMsg = 'No messages yet';
                if (chatData.last_message) {
                    if (typeof chatData.last_message === 'string') {
                        lastMsg = chatData.last_message;
                    } else if (chatData.last_message.message) {
                        lastMsg = chatData.last_message.message;
                    }
                }
                lastMsg = lastMsg.substring(0, 30);
                
                newChatItem.innerHTML = `
                    <div class="flex items-center space-x-3">
                        ${avatarHtml}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-900 truncate">${chatData.name || 'User'}</p>
                                ${badgeHtml}
                            </div>
                            <p class="text-xs text-gray-500 truncate">${lastMsg}</p>
                        </div>
                    </div>
                `;
                
                const groupChat = chatUsersList.querySelector('a[href="{{ route('chat') }}"]');
                if (groupChat) {
                    chatUsersList.insertBefore(newChatItem, groupChat.nextSibling);
                } else {
                    chatUsersList.insertBefore(newChatItem, chatUsersList.firstChild);
                }
            }
        }

        function updateUnreadBadge(chatItem, count) {
            const nameContainer = chatItem.querySelector('.flex.items-center.justify-between');
            if (!nameContainer) return;
            
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
    });
    </script>
</body>
</html>