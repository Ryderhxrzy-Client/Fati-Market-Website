@extends('layouts.admin-dashboard')

@section('title', 'Conversations')

@section('content')
<div style="display: flex; flex-direction: column; height: 100%; margin: -24px -24px -24px -24px; padding: 24px;">
    <!-- Header -->
    <div style="margin-bottom: 16px;">
        <h3 class="text-lg font-bold text-gray-900">Conversations</h3>
        <p class="text-sm text-gray-600">Monitor and manage user conversations</p>
    </div>

    <!-- Search Bar -->
    <div style="margin-bottom: 16px;">
        <input type="search" placeholder="Search conversations..." id="searchInput" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
    </div>

    <!-- Main Container -->
    <div style="display: flex; gap: 16px; flex: 1; min-height: 0;">
        <!-- Conversations List (Left) -->
        <div style="width: 35%; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; flex-direction: column; overflow: hidden;">
            <div id="conversationsList" style="flex: 1; overflow-y: auto; border-right: 1px solid #e5e7eb;">
                <!-- Conversations will be loaded here -->
                <div class="p-6 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2 block"></i>
                    <p class="text-sm">Loading conversations...</p>
                </div>
            </div>
        </div>

        <!-- Chat Area (Right) -->
        <div style="flex: 1; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; flex-direction: column; overflow: hidden;">
            <!-- Chat Header -->
            <div id="chatHeader" class="px-6 py-4 border-b border-gray-200 flex justify-between items-center" style="flex-shrink: 0;">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center">
                        <i class="fas fa-user text-gray-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm">Select a conversation</p>
                        <p class="text-xs text-gray-500">Choose from the list to start</p>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="messagesArea" class="flex-1 overflow-y-auto p-6 space-y-4" style="background: #fafafa;">
                <div class="text-center text-gray-500 mt-20">
                    <i class="fas fa-comments text-4xl mb-3 block text-gray-300"></i>
                    <p class="text-sm">Select a conversation to view messages</p>
                </div>
            </div>

            <!-- Message Input -->
            <div id="messageInput" class="px-6 py-4 border-t border-gray-200 hidden" style="flex-shrink: 0;">
                <div class="flex gap-3">
                    <input type="text" id="messageField" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button onclick="sendMessage()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let token = null;
let selectedConversation = null;
let allConversations = [];

function getToken() {
    const metaToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content');
    if (metaToken && metaToken.trim()) {
        return metaToken;
    }
    return sessionStorage.getItem('admin_token') ||
           localStorage.getItem('admin_token') ||
           sessionStorage.getItem('token') ||
           localStorage.getItem('token');
}

document.addEventListener('DOMContentLoaded', async function() {
    token = getToken();
    console.log('Token available:', !!token);

    if (!token) {
        document.getElementById('messagesArea').innerHTML = `
            <div class="text-center text-red-500 mt-20">
                <i class="fas fa-lock text-4xl mb-3 block text-red-300"></i>
                <p class="text-sm">Authentication required</p>
                <p class="text-xs mt-2">Please log in again</p>
            </div>
        `;
        return;
    }

    await loadConversations();
});

async function loadConversations() {
    try {
        const response = await fetch('https://fati-api.alertaraqc.com/api/conversations', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        const conversations = Array.isArray(data) ? data : (data.data || data.conversations || []);

        allConversations = conversations;
        renderConversations(conversations);
    } catch (error) {
        console.error('Error loading conversations:', error);
        document.getElementById('messagesArea').innerHTML = `
            <div class="text-center text-red-500 mt-20">
                <i class="fas fa-exclamation-circle text-4xl mb-3 block text-red-300"></i>
                <p class="text-sm font-medium">Error loading conversations</p>
                <p class="text-xs mt-2">${error.message}</p>
                ${error.message.includes('401') ? '<p class="text-xs mt-2 text-red-600">Please check your authentication token</p>' : ''}
            </div>
        `;
    }
}

function renderConversations(conversations) {
    const list = document.getElementById('conversationsList');

    if (conversations.length === 0) {
        list.innerHTML = `
            <div class="p-6 text-center text-gray-500">
                <i class="fas fa-inbox text-2xl mb-2 block text-gray-300"></i>
                <p class="text-sm">No conversations found</p>
            </div>
        `;
        return;
    }

    list.innerHTML = conversations.map((conv, index) => {
        const userEmail = conv.other_user_email || 'Unknown';
        const userName = conv.first_name && conv.last_name ?
            `${conv.first_name} ${conv.last_name}` :
            (conv.first_name || conv.last_name || userEmail.split('@')[0]);
        const initials = userName.split(' ').map(n => n[0]).join('').toUpperCase();
        const unreadCount = conv.unread_count || 0;
        const lastMessage = conv.latest_message || 'No messages yet';
        const itemTitle = conv.item_title || 'No item';
        const itemStatus = conv.item_status || 'public';
        const userType = userEmail.includes('student.fatima') ? 'Student' : 'User';
        const statusBadgeColor = {
            'public': '#10b981',
            'private': '#6b7280',
            'sold': '#ef4444',
            'acquired': '#8b5cf6'
        };

        return `
            <div class="p-4 hover:bg-gray-50 cursor-pointer transition border-b border-gray-100 conversation-item"
                 data-conv-index="${index}"
                 data-item-id="${conv.item_id}"
                 data-user-id="${conv.other_user_id}"
                 style="display: flex; gap: 12px; align-items: flex-start;">

                <div style="flex-shrink: 0;">
                    ${conv.profile_picture ?
                        `<img src="${conv.profile_picture}" alt="${userName}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;">` :
                        `<div style="width: 44px; height: 44px; border-radius: 50%; background: #16a34a; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">${initials}</div>`
                    }
                </div>

                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                        <p style="font-weight: 600; color: #1f2937; margin: 0; font-size: 14px;">${userName}</p>
                        ${unreadCount > 0 ? `<span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #ef4444; color: white; font-size: 11px; border-radius: 50%; font-weight: bold;">${unreadCount}</span>` : ''}
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                        <span style="background: ${statusBadgeColor[itemStatus]}; color: white; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; text-transform: capitalize;">${itemStatus}</span>
                        <span style="color: #6b7280; font-size: 11px;">•</span>
                        <span style="color: #6b7280; font-size: 11px; font-weight: 500;">${userType}</span>
                    </div>
                    <p style="color: #6b7280; font-size: 12px; margin: 2px 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; font-weight: 500;">${itemTitle}</p>
                    <p style="color: #9ca3af; font-size: 12px; margin: 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">${lastMessage}</p>
                </div>

                ${conv.item_photo ? `
                    <div style="flex-shrink: 0;">
                        <img src="${conv.item_photo}" alt="${itemTitle}" style="width: 60px; height: 60px; border-radius: 4px; object-fit: cover;">
                    </div>
                ` : ''}
            </div>
        `;
    }).join('');

    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function() {
            loadConversationMessages(this);
        });
    });
}

async function loadConversationMessages(element) {
    document.querySelectorAll('.conversation-item').forEach(i => {
        i.style.backgroundColor = '';
    });
    element.style.backgroundColor = '#f3f4f6';

    const convIndex = element.dataset.convIndex;
    const itemId = element.dataset.itemId;
    const userId = element.dataset.userId;

    selectedConversation = allConversations[convIndex];

    const userName = selectedConversation.first_name && selectedConversation.last_name ?
        `${selectedConversation.first_name} ${selectedConversation.last_name}` :
        (selectedConversation.first_name || selectedConversation.other_user_email.split('@')[0]);
    const initials = userName.split(' ').map(n => n[0]).join('').toUpperCase();

    const itemStatus = selectedConversation.item_status || 'public';
    const userType = selectedConversation.other_user_email.includes('student.fatima') ? 'Student' : 'User';
    const statusBadgeColor = {
        'public': '#10b981',
        'private': '#6b7280',
        'sold': '#ef4444',
        'acquired': '#8b5cf6'
    };

    document.getElementById('chatHeader').innerHTML = `
        <div style="display: flex; gap: 12px; align-items: center; width: 100%;">
            ${selectedConversation.profile_picture ?
                `<img src="${selectedConversation.profile_picture}" alt="${userName}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">` :
                `<div style="width: 48px; height: 48px; border-radius: 50%; background: #16a34a; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">${initials}</div>`
            }
            <div style="flex: 1;">
                <p style="font-weight: 600; color: #1f2937; margin: 0; font-size: 14px;">${userName}</p>
                <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px;">
                    <span style="background: ${statusBadgeColor[itemStatus]}; color: white; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; text-transform: capitalize;">${itemStatus}</span>
                    <span style="color: #6b7280; font-size: 11px;">•</span>
                    <span style="color: #6b7280; font-size: 11px; font-weight: 500;">${userType}</span>
                </div>
                <p style="color: #6b7280; font-size: 12px; margin: 4px 0 0 0;">${selectedConversation.item_title || 'Item'}</p>
            </div>
            ${selectedConversation.item_photo ? `
                <img src="${selectedConversation.item_photo}" alt="${selectedConversation.item_title}" style="width: 56px; height: 56px; border-radius: 4px; object-fit: cover; flex-shrink: 0;">
            ` : ''}
        </div>
    `;

    document.getElementById('messageInput').classList.remove('hidden');

    try {
        const response = await fetch(`https://fati-api.alertaraqc.com/api/messages/${itemId}?other_user_id=${userId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        const messages = Array.isArray(data) ? data : (data.data || data.messages || []);

        renderMessages(messages);
    } catch (error) {
        console.error('Error loading messages:', error);
        document.getElementById('messagesArea').innerHTML = `
            <div class="text-center text-red-500 mt-20">
                <i class="fas fa-exclamation-circle text-4xl mb-3 block text-red-300"></i>
                <p class="text-sm font-medium">Error loading messages</p>
                <p class="text-xs mt-2">${error.message}</p>
            </div>
        `;
    }
}

function renderMessages(messages) {
    const area = document.getElementById('messagesArea');

    if (messages.length === 0) {
        area.innerHTML = `
            <div class="text-center text-gray-500 mt-20">
                <i class="fas fa-comments text-4xl mb-3 block text-gray-300"></i>
                <p class="text-sm">No messages yet. Start the conversation!</p>
            </div>
        `;
        return;
    }

    area.innerHTML = messages.map(msg => {
        const isAdmin = msg.sender_id !== selectedConversation.other_user_id;
        const senderName = msg.sender_name || 'User';
        const timestamp = new Date(msg.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        return `
            <div style="display: flex; ${isAdmin ? 'justify-content: flex-end;' : 'justify-content: flex-start;'} margin-bottom: 12px;">
                <div style="display: flex; gap: 8px; ${isAdmin ? 'flex-direction: row-reverse;' : ''} max-width: 70%;">
                    ${msg.sender_profile_picture ?
                        `<img src="${msg.sender_profile_picture}" alt="${senderName}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">` :
                        `<div style="width: 32px; height: 32px; border-radius: 50%; ${isAdmin ? 'background: #16a34a;' : 'background: #d1d5db;'} color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; flex-shrink: 0;">${senderName[0]}</div>`
                    }
                    <div style="display: flex; flex-direction: column; ${isAdmin ? 'align-items: flex-end;' : 'align-items: flex-start;'}">
                        <p style="font-size: 11px; color: #6b7280; margin-bottom: 2px;">${senderName}</p>
                        <div style="background: ${isAdmin ? '#16a34a;' : '#e5e7eb;'} color: ${isAdmin ? 'white;' : '#1f2937;'} border-radius: 8px; padding: 8px 12px; word-wrap: break-word;">
                            <p style="font-size: 13px; margin: 0;">${escapeHtml(msg.message)}</p>
                        </div>
                        <p style="font-size: 11px; color: ${isAdmin ? '#16a34a;' : '#9ca3af;'} margin-top: 4px;">${timestamp}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    area.scrollTop = area.scrollHeight;
}

async function sendMessage() {
    if (!selectedConversation) return;

    const messageField = document.getElementById('messageField');
    const message = messageField.value.trim();

    if (!message) return;

    try {
        const response = await fetch(`https://fati-api.alertaraqc.com/api/messages/${selectedConversation.item_id}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                receiver_id: selectedConversation.other_user_id,
                message: message
            })
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        messageField.value = '';
        await loadConversationMessages(document.querySelector('.conversation-item[style*="background"]') || document.querySelector('.conversation-item'));
    } catch (error) {
        console.error('Error sending message:', error);
        alert('Error sending message: ' + error.message);
    }
}

document.getElementById('searchInput').addEventListener('keyup', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const filtered = allConversations.filter(conv => {
        const userName = conv.first_name && conv.last_name ?
            `${conv.first_name} ${conv.last_name}` :
            (conv.first_name || conv.other_user_email.split('@')[0]);
        const itemTitle = conv.item_title || '';
        return userName.toLowerCase().includes(searchTerm) || itemTitle.toLowerCase().includes(searchTerm);
    });
    renderConversations(filtered);
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
@endsection
