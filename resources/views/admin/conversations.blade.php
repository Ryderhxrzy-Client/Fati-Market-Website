@extends('layouts.admin-dashboard')

@section('title', 'Conversations')

@section('content')
<div style="display: flex; flex-direction: column; height: 100%; margin: -24px -24px -24px -24px; padding: 24px;">
    <!-- Header -->
    <div style="margin-bottom: 16px;">
        <h3>Conversations</h3>
        <p>Monitor and manage user conversations</p>
    </div>

    <!-- Search Bar -->
    <div style="margin-bottom: 16px;">
        <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search conversations..." id="searchInput" class="fm-input"></span>
    </div>

    <!-- Main Container -->
    <div style="display: flex; gap: 16px; flex: 1; min-height: 0;">
        <!-- Conversations List (Left) -->
        <div style="width: 35%; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; flex-direction: column; overflow: hidden;">
            <div id="conversationsList" style="flex: 1; overflow-y: auto; border-right: 1px solid var(--line);">
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
                        <p class="cell-sub">Choose from the list to start</p>
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

            <!-- Receipt lightbox / item panel -->
            <div id="chatOverlay"
                 onclick="if (event.target === this) closeOverlay()"
                 style="display: none; position: fixed; inset: 0; background: rgba(12, 48, 33, 0.55); z-index: 60; align-items: center; justify-content: center; padding: 32px;">
                <div id="chatOverlayBody" style="background: white; border-radius: 12px; max-width: 640px; width: 100%; max-height: 88vh; overflow-y: auto; padding: 24px;"></div>
            </div>

            <!-- Message Input -->
            <div id="messageInput" class="px-6 py-4 border-t border-gray-200 hidden" style="flex-shrink: 0;">
                <div class="flex gap-3">
                    <input type="text" id="messageField" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button onclick="sendMessage()" class="fm-btn primary">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const API = 'https://fati-api.alertaraqc.com/api';

let token = null;
let selectedConversation = null;
let allConversations = [];
let busyAction = false;

// Item-status badge colours. An unknown status falls back to grey instead of
// `undefined`, which used to paint a white badge with white text.
const statusBadgeColor = {
    public: '#10b981',
    private: '#64748b',
    pending: '#d97706',
    acquired: '#7c3aed',
    reserved: '#2563eb',
    sold: '#dc2626',
    rejected: '#b91c1c',
};
const statusColor = (status) => statusBadgeColor[status] || '#64748b';

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
        const response = await fetch(`${API}/conversations`, {
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

        return `
            <div class="p-4 hover:bg-gray-50 cursor-pointer transition border-b border-gray-100 conversation-item"
                 data-conv-index="${index}"
                 data-item-id="${conv.item_id}"
                 data-user-id="${conv.other_user_id}"
                 style="display: flex; gap: 12px; align-items: flex-start;">

                <div style="flex-shrink: 0;">
                    ${conv.profile_picture ?
                        `<img src="${conv.profile_picture}" alt="${userName}" style="width: 44px; height: 44px; border-radius: 50%; object-fit: cover;">` :
                        `<div style="width: 44px; height: 44px; border-radius: 50%; background: var(--brand-600); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">${initials}</div>`
                    }
                </div>

                <div style="flex: 1; min-width: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 4px;">
                        <p style="font-weight: 600; color: var(--ink-900); margin: 0; font-size: 14px;">${userName}</p>
                        ${unreadCount > 0 ? `<span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; background: #ef4444; color: white; font-size: 11px; border-radius: 50%; font-weight: bold;">${unreadCount}</span>` : ''}
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 4px;">
                        <span style="background: ${statusColor(itemStatus)}; color: white; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; text-transform: capitalize;">${itemStatus}</span>
                        <span style="color: var(--ink-500); font-size: 11px;">•</span>
                        <span style="color: var(--ink-500); font-size: 11px; font-weight: 500;">${userType}</span>
                    </div>
                    <p style="color: var(--ink-500); font-size: 12px; margin: 2px 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; font-weight: 500;">${itemTitle}</p>
                    <p style="color: var(--ink-400); font-size: 12px; margin: 0; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">${lastMessage}</p>
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
    element.style.backgroundColor = 'var(--surface-sunk)';

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

    document.getElementById('chatHeader').innerHTML = `
        <div style="display: flex; gap: 12px; align-items: center; width: 100%;">
            ${selectedConversation.profile_picture ?
                `<img src="${selectedConversation.profile_picture}" alt="${userName}" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">` :
                `<div style="width: 48px; height: 48px; border-radius: 50%; background: var(--brand-600); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">${initials}</div>`
            }
            <div style="flex: 1;">
                <p style="font-weight: 600; color: var(--ink-900); margin: 0; font-size: 14px;">${userName}</p>
                <div style="display: flex; gap: 8px; align-items: center; margin-top: 4px;">
                    <span style="background: ${statusColor(itemStatus)}; color: white; font-size: 11px; padding: 2px 8px; border-radius: 4px; font-weight: 600; text-transform: capitalize;">${itemStatus}</span>
                    <span style="color: var(--ink-500); font-size: 11px;">•</span>
                    <span style="color: var(--ink-500); font-size: 11px; font-weight: 500;">${userType}</span>
                </div>
                <p style="color: var(--ink-500); font-size: 12px; margin: 4px 0 0 0;">${selectedConversation.item_title || 'Item'}</p>
            </div>
            ${selectedConversation.item_photo ? `
                <img src="${selectedConversation.item_photo}" alt="${selectedConversation.item_title}" style="width: 56px; height: 56px; border-radius: 4px; object-fit: cover; flex-shrink: 0;">
            ` : ''}
        </div>
    `;

    document.getElementById('messageInput').classList.remove('hidden');

    try {
        const response = await fetch(`${API}/messages/${itemId}?other_user_id=${userId}`, {
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

        // A checkout, a GCash receipt or an order decision. These carry the
        // order itself, so they are drawn as a card that Ofelia can act on -
        // the same decisions the mobile admin app offers, from the same
        // server-supplied `available_actions`.
        if (msg.kind && msg.kind !== 'text' && msg.order) {
            return renderOrderCard(msg, isAdmin, senderName, timestamp);
        }

        // A seller's fresh listing: the offer card, with the review decisions.
        if (msg.kind === 'item_listed' && msg.item_card) {
            return renderItemOfferCard(msg, isAdmin, senderName, timestamp);
        }

        return `
            <div style="display: flex; ${isAdmin ? 'justify-content: flex-end;' : 'justify-content: flex-start;'} margin-bottom: 12px;">
                <div style="display: flex; gap: 8px; ${isAdmin ? 'flex-direction: row-reverse;' : ''} max-width: 70%;">
                    ${msg.sender_profile_picture ?
                        `<img src="${msg.sender_profile_picture}" alt="${senderName}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0;">` :
                        `<div style="width: 32px; height: 32px; border-radius: 50%; ${isAdmin ? 'background: var(--brand-600);' : 'background: var(--line-strong);'} color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; flex-shrink: 0;">${senderName[0]}</div>`
                    }
                    <div style="display: flex; flex-direction: column; ${isAdmin ? 'align-items: flex-end;' : 'align-items: flex-start;'}">
                        <p style="font-size: 11px; color: var(--ink-500); margin-bottom: 2px;">${senderName}</p>
                        <div style="background: ${isAdmin ? 'var(--brand-600);' : 'var(--line);'} color: ${isAdmin ? 'white;' : 'var(--ink-900);'} border-radius: 8px; padding: 8px 12px; word-wrap: break-word;">
                            <p style="font-size: 13px; margin: 0;">${escapeHtml(msg.message)}</p>
                        </div>
                        <p style="font-size: 11px; color: ${isAdmin ? 'var(--brand-600);' : 'var(--ink-400);'} margin-top: 4px;">${timestamp}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    area.scrollTop = area.scrollHeight;
}

// ── Order cards ──────────────────────────────────────────────────────────

const PESO = '\u20B1';

function peso(amount) {
    const value = Number(amount);
    if (!isFinite(value)) return PESO + '0.00';
    return PESO + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function paymentMethodLabel(method) {
    return { gcash: 'GCash', points_full: 'Points only', cash: 'Cash at store' }[method]
        || (method || 'Unknown');
}

/**
 * Whether the money has actually arrived.
 *
 * Deliberately separate from the order status: an order can be reserved while
 * its payment is still unverified, and the payment is what is being asked
 * about.
 */
function paymentStateBadge(order) {
    const status = order.payment_status;
    const map = {
        verified: [order.is_full_points_checkout ? 'Paid with points' : 'Paid', '#065f46', '#d1fae5'],
        proof_submitted: ['Checking payment', '#92400e', '#fef3c7'],
        rejected: ['Payment declined', '#991b1b', '#fee2e2'],
    };
    const [label, colour, background] = map[status] || ['Not paid yet', '#92400e', '#fef3c7'];

    return badge(label, colour, background);
}

function orderStatusBadge(status) {
    const map = {
        pending_payment: ['Awaiting payment', '#92400e', '#fef3c7'],
        payment_proof_submitted: ['Proof submitted', '#1e40af', '#dbeafe'],
        payment_verified: ['Payment verified', '#1e40af', '#dbeafe'],
        reserved: ['Reserved', '#1e40af', '#dbeafe'],
        ready_for_pickup: ['Ready for pickup', '#3730a3', '#e0e7ff'],
        completed: ['Completed', '#065f46', '#d1fae5'],
        cancelled: ['Cancelled', 'var(--ink-700)', 'var(--surface-sunk)'],
        rejected: ['Rejected', '#991b1b', '#fee2e2'],
    };
    const [label, colour, background] = map[status] || [status, 'var(--ink-700)', 'var(--surface-sunk)'];

    return badge(label, colour, background);
}

function badge(label, colour, background) {
    return `<span style="background: ${background}; color: ${colour}; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; white-space: nowrap;">${escapeHtml(label)}</span>`;
}

function summaryRow(label, value, strong) {
    return `
        <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 3px 0;">
            <span style="color: var(--ink-500);">${escapeHtml(label)}</span>
            <span style="color: var(--ink-900); ${strong ? 'font-weight: 700;' : ''} text-align: right;">${escapeHtml(value)}</span>
        </div>
    `;
}

function renderOrderCard(msg, isAdmin, senderName, timestamp) {
    const order = msg.order;
    const item = order.item || {};
    const photo = (item.photos && item.photos[0]) || null;
    const heading = {
        order_placed: ['fa-cart-shopping', 'Order placed'],
        payment_submitted: ['fa-receipt', 'Payment sent'],
        order_update: ['fa-bell', 'Order update'],
    }[msg.kind] || ['fa-receipt', 'Order'];

    return `
        <div style="display: flex; ${isAdmin ? 'justify-content: flex-end;' : 'justify-content: flex-start;'} margin-bottom: 12px;">
            <div style="max-width: 420px; width: 100%; background: white; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden;">

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface-sunk); border-bottom: 1px solid var(--line);">
                    <span style="font-size: 12px; font-weight: 700; color: var(--brand-700);">
                        <i class="fas ${heading[0]}"></i> ${heading[1]}
                    </span>
                    <span style="font-size: 11px; color: var(--ink-500);">${escapeHtml(order.receipt_no || ('#' + order.transaction_id))}</span>
                </div>

                <div style="padding: 14px;">
                    <div onclick="openItem(${order.item_id})"
                         style="display: flex; gap: 10px; align-items: center; cursor: pointer; margin-bottom: 12px;">
                        ${photo
                            ? `<img src="${escapeAttr(photo)}" alt="" style="width: 56px; height: 56px; border-radius: 6px; object-fit: cover; flex-shrink: 0;">`
                            : `<div style="width: 56px; height: 56px; border-radius: 6px; background: var(--surface-sunk); display: flex; align-items: center; justify-content: center; color: var(--ink-400); flex-shrink: 0;"><i class="fas fa-image"></i></div>`}
                        <div style="min-width: 0;">
                            <p style="margin: 0; font-size: 13px; font-weight: 600; color: var(--ink-900);">${escapeHtml(item.title || ('Item #' + order.item_id))}</p>
                            <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--ink-500);">${peso(order.amount_due)} due</p>
                            <p style="margin: 2px 0 0 0; font-size: 11px; color: var(--brand-600); font-weight: 600;">Click to view item</p>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--surface-sunk); padding-top: 8px;">
                        ${summaryRow('Price', peso(order.subtotal))}
                        ${order.points_used > 0 ? summaryRow(order.points_used + ' point(s) used', '-' + peso(order.points_discount_amount)) : ''}
                        ${summaryRow('Amount due', peso(order.amount_due), true)}
                        ${summaryRow('Payment', paymentMethodLabel(order.payment_method))}
                        ${order.payment_reference ? summaryRow('Reference', order.payment_reference) : ''}
                    </div>

                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px;">
                        ${paymentStateBadge(order)}
                        ${orderStatusBadge(order.status)}
                    </div>

                    ${order.payment_proof ? `
                        <img src="${escapeAttr(order.payment_proof)}" alt="Payment receipt"
                             data-proof="${escapeAttr(order.payment_proof)}"
                             data-reference="${escapeAttr(order.payment_reference || '')}"
                             onclick="openProof(this.dataset.proof, this.dataset.reference)"
                             style="margin-top: 10px; width: 100%; height: 150px; object-fit: cover; border-radius: 8px; cursor: pointer;">
                        <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--ink-500);">Click the receipt to see it in full</p>
                    ` : ''}

                    ${msg.kind === 'order_update' ? `
                        <p style="margin: 10px 0 0 0; padding: 8px 10px; background: var(--surface-sunk); border-radius: 6px; font-size: 12px; color: var(--ink-700); white-space: pre-line;">${escapeHtml(msg.message)}</p>
                    ` : ''}

                    ${carriesActions(msg) ? orderActionsHtml(order) : ''}

                    <p style="margin: 8px 0 0 0; font-size: 11px; color: var(--ink-400);">${escapeHtml(senderName)} &middot; ${timestamp}</p>
                </div>
            </div>
        </div>
    `;
}

/**
 * Which card in the thread carries the buttons.
 *
 * Every order card points at the same order, so only one offers the decisions:
 * the receipt if the buyer sent one, otherwise the order itself.
 */
function carriesActions(msg) {
    return msg.kind === 'payment_submitted'
        || (msg.kind === 'order_placed' && !msg.order.payment_proof);
}

/**
 * The decisions the server says are still open.
 *
 * `available_actions` is admin-only, so a payload without it simply renders no
 * buttons rather than offering something the API would refuse.
 */
function orderActionsHtml(order) {
    const actions = order.available_actions || [];
    if (actions.length === 0) return '';

    const id = order.transaction_id;
    const buttons = [];

    if (actions.includes('verify_payment')) {
        buttons.push(actionButton(id, 'verify-payment', 'Approve', 'var(--brand-600)', false));
    }
    if (actions.includes('complete')) {
        buttons.push(actionButton(id, 'complete', 'Complete', 'var(--brand-600)', false));
    }
    if (actions.includes('mark_ready_for_pickup')) {
        buttons.push(actionButton(id, 'ready-for-pickup', 'Ready for pickup', 'var(--info)', false));
    }

    // Declining a submitted proof and cancelling an order are the same button
    // to Ofelia; which endpoint it hits depends on where the order stands.
    if (actions.includes('reject_payment')) {
        buttons.push(actionButton(id, 'reject-payment', 'Decline', 'var(--danger)', true));
    } else if (actions.includes('cancel')) {
        buttons.push(actionButton(id, 'cancel', 'Cancel order', 'var(--danger)', true));
    }

    return `
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; border-top: 1px solid var(--surface-sunk); padding-top: 12px;">
            ${buttons.join('')}
        </div>
    `;
}

function actionButton(id, endpoint, label, colour, outlined) {
    const style = outlined
        ? `background: white; color: ${colour}; border: 1px solid ${colour};`
        : `background: ${colour}; color: white; border: 1px solid ${colour};`;

    // Endpoint and label are both fixed strings chosen just above, so single
    // quotes inside the attribute are safe here.
    return `<button onclick="runOrderAction(${id}, '${endpoint}', '${label}')"
                    style="${style} font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 6px; cursor: pointer;">${escapeHtml(label)}</button>`;
}

async function runOrderAction(transactionId, endpoint, label) {
    if (busyAction) return;

    // Declining and cancelling both tell the buyer why, in this same thread.
    const needsReason = endpoint === 'reject-payment' || endpoint === 'cancel';
    let reason = null;

    if (needsReason) {
        reason = window.prompt(`${label}: give the buyer a reason.`);
        if (reason === null) return;
        if (!reason.trim()) {
            alert('A reason is required.');
            return;
        }
    } else if (!window.confirm(`${label} this order?`)) {
        return;
    }

    busyAction = true;

    try {
        const response = await fetch(`${API}/admin/transactions/${transactionId}/${endpoint}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(needsReason ? { reason: reason.trim() } : {}),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || `HTTP ${response.status}`);
        }

        await refreshThread();
    } catch (error) {
        alert(`Could not ${label.toLowerCase()}: ${error.message}`);
    } finally {
        busyAction = false;
    }
}

/** Re-read the open thread so the card shows the decision that was just made. */
async function refreshThread() {
    if (!selectedConversation) return;

    const selector = `.conversation-item[data-item-id="${selectedConversation.item_id}"][data-user-id="${selectedConversation.other_user_id}"]`;
    const element = document.querySelector(selector);

    if (element) await loadConversationMessages(element);
}

// ── Listing offers ───────────────────────────────────────────────────────

function renderItemOfferCard(msg, isAdmin, senderName, timestamp) {
    const item = msg.item_card;
    const photo = (item.photos && item.photos[0]) || null;
    const pending = (item.status || '').toLowerCase() === 'pending';
    // A price on a pending listing IS the acceptance; from there the next
    // move is scheduling the turnover, not deciding again.
    const accepted = pending && item.acquisition_price;

    return `
        <div style="display: flex; ${isAdmin ? 'justify-content: flex-end;' : 'justify-content: flex-start;'} margin-bottom: 12px;">
            <div style="max-width: 420px; width: 100%; background: white; border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); overflow: hidden;">

                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface-sunk); border-bottom: 1px solid var(--line);">
                    <span style="font-size: 12px; font-weight: 700; color: var(--brand-700);">
                        <i class="fas fa-tag"></i> Item offer
                    </span>
                    <span style="font-size: 11px; color: var(--ink-500);">Item #${item.item_id}</span>
                </div>

                <div style="padding: 14px;">
                    <div onclick="openItem(${item.item_id})"
                         style="display: flex; gap: 10px; align-items: center; cursor: pointer; margin-bottom: 12px;">
                        ${photo
                            ? `<img src="${escapeAttr(photo)}" alt="" style="width: 56px; height: 56px; border-radius: 6px; object-fit: cover; flex-shrink: 0;">`
                            : `<div style="width: 56px; height: 56px; border-radius: 6px; background: var(--surface-sunk); display: flex; align-items: center; justify-content: center; color: var(--ink-400); flex-shrink: 0;"><i class="fas fa-image"></i></div>`}
                        <div style="min-width: 0;">
                            <p style="margin: 0; font-size: 13px; font-weight: 600; color: var(--ink-900);">${escapeHtml(item.title || ('Item #' + item.item_id))}</p>
                            <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--ink-500);">Asking ${peso(item.seller_asking_price)}</p>
                            <p style="margin: 2px 0 0 0; font-size: 11px; color: var(--brand-600); font-weight: 600;">Click to view item</p>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--surface-sunk); padding-top: 8px;">
                        ${summaryRow('Asking price', peso(item.seller_asking_price), true)}
                        ${item.acquisition_price ? summaryRow('Store offer', peso(item.acquisition_price)) : ''}
                        ${item.meetup_schedule ? summaryRow('Meet-up', new Date(item.meetup_schedule).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })) : ''}
                    </div>

                    <div style="margin-top: 10px;">${badge(item.status || 'pending', '#374151', '#f3f4f6')}</div>

                    ${item.rejected_reason ? `
                        <p style="margin: 10px 0 0 0; padding: 8px 10px; background: var(--danger-bg, #FCE8E6); border-radius: 6px; font-size: 12px; color: var(--danger);">${escapeHtml(item.rejected_reason)}</p>
                    ` : ''}

                    ${accepted ? `
                        <div style="display: flex; gap: 8px; margin-top: 12px; border-top: 1px solid var(--surface-sunk); padding-top: 12px;">
                            <button onclick="scheduleMeetup(${item.item_id})"
                                    style="background: #16a34a; color: white; border: 1px solid #16a34a; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 6px; cursor: pointer;">
                                ${item.meetup_schedule ? 'Change meet-up schedule' : 'Set meet-up schedule'}
                            </button>
                        </div>
                    ` : pending ? `
                        <div style="display: flex; gap: 8px; margin-top: 12px; border-top: 1px solid var(--surface-sunk); padding-top: 12px;">
                            <button onclick="acceptOffer(${item.item_id}, '${escapeAttr(item.seller_asking_price || '')}')"
                                    style="background: #16a34a; color: white; border: 1px solid #16a34a; font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 6px; cursor: pointer;">Accept &middot; set price</button>
                            <button onclick="rejectOffer(${item.item_id})"
                                    style="background: white; color: var(--danger); border: 1px solid var(--danger); font-size: 12px; font-weight: 600; padding: 7px 14px; border-radius: 6px; cursor: pointer;">Reject</button>
                        </div>
                    ` : ''}

                    <p style="margin: 8px 0 0 0; font-size: 11px; color: var(--ink-400);">${escapeHtml(senderName)} &middot; ${timestamp}</p>
                </div>
            </div>
        </div>
    `;
}

/**
 * Accepting an offer is setting the acquisition price - the same first step
 * the inventory workflow takes, against the same endpoint.
 */
async function acceptOffer(itemId, askingPrice) {
    if (busyAction) return;

    const price = window.prompt('Acquisition price - what the store pays the seller:', askingPrice);
    if (price === null) return;
    if (!price.trim() || isNaN(Number(price))) {
        alert('Enter a valid peso amount, e.g. 300 or 299.50.');
        return;
    }

    busyAction = true;

    try {
        const response = await fetch(`${API}/admin/items/${itemId}/acquisition-price`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ acquisition_price: price.trim() }),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        await refreshThread();
    } catch (error) {
        alert(`Could not accept the offer: ${error.message}`);
    } finally {
        busyAction = false;
    }
}

/**
 * When the seller comes in. Sent to the same endpoint the mobile card and the
 * inventory workflow use; the 6h/1h/30m reminders count down from it.
 */
async function scheduleMeetup(itemId) {
    if (busyAction) return;

    const raw = window.prompt('Meet-up date and time (YYYY-MM-DD HH:MM):');
    if (raw === null) return;

    const when = new Date(raw.trim().replace(' ', 'T'));
    if (isNaN(when.getTime())) {
        alert('Enter the schedule as YYYY-MM-DD HH:MM, e.g. 2026-09-02 14:30.');
        return;
    }

    busyAction = true;

    try {
        const response = await fetch(`${API}/admin/items/${itemId}/meetup`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ meetup_schedule: raw.trim() }),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        await refreshThread();
    } catch (error) {
        alert(`Could not save the schedule: ${error.message}`);
    } finally {
        busyAction = false;
    }
}

async function rejectOffer(itemId) {
    if (busyAction) return;

    const reason = window.prompt('Reject this offer - give the seller a reason:');
    if (reason === null) return;
    if (!reason.trim()) {
        alert('A reason is required.');
        return;
    }

    busyAction = true;

    try {
        const response = await fetch(`${API}/admin/items/${itemId}/reject`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ reason: reason.trim() }),
        });
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        await refreshThread();
    } catch (error) {
        alert(`Could not reject the offer: ${error.message}`);
    } finally {
        busyAction = false;
    }
}

// ── Overlays ─────────────────────────────────────────────────────────────

function showOverlay(html) {
    document.getElementById('chatOverlayBody').innerHTML = html;
    document.getElementById('chatOverlay').style.display = 'flex';
}

function closeOverlay() {
    document.getElementById('chatOverlay').style.display = 'none';
    document.getElementById('chatOverlayBody').innerHTML = '';
}

function openProof(url, reference) {
    showOverlay(`
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--ink-900);">Payment receipt</h4>
            <button onclick="closeOverlay()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: var(--ink-500);">&times;</button>
        </div>
        ${reference ? `<p style="margin: 0 0 12px 0; font-size: 13px; color: var(--ink-700);">Reference: <strong>${escapeHtml(reference)}</strong></p>` : ''}
        <img src="${escapeAttr(url)}" alt="Payment receipt" style="width: 100%; border-radius: 8px;">
    `);
}

/** The listing behind an order, opened from the card's photo. */
async function openItem(itemId) {
    showOverlay('<p style="font-size: 13px; color: var(--ink-500);">Loading item...</p>');

    try {
        const response = await fetch(`${API}/items/${itemId}`, {
            headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' },
        });

        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        const item = payload.data || {};
        const photos = item.photos || [];

        showOverlay(`
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <h4 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--ink-900);">${escapeHtml(item.title || 'Item')}</h4>
                <button onclick="closeOverlay()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: var(--ink-500);">&times;</button>
            </div>

            ${photos.length ? `<div style="display: flex; gap: 8px; overflow-x: auto; margin-bottom: 12px;">
                ${photos.map(url => `<img src="${escapeAttr(url)}" alt="" style="height: 180px; border-radius: 8px; object-fit: cover;">`).join('')}
            </div>` : ''}

            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 20px; font-weight: 700; color: var(--brand-700);">${peso(item.public_price || item.seller_asking_price || 0)}</span>
                ${badge(item.status || 'unknown', 'var(--ink-700)', 'var(--surface-sunk)')}
                ${item.reward_points ? badge(`Earn ${item.reward_points} point(s)`, '#92400e', '#fef3c7') : ''}
            </div>

            ${item.description ? `<p style="font-size: 13px; color: var(--ink-700); white-space: pre-line;">${escapeHtml(item.description)}</p>` : ''}

            <div style="border-top: 1px solid var(--surface-sunk); margin-top: 12px; padding-top: 10px;">
                ${item.acquisition_price ? summaryRow('Acquisition price', peso(item.acquisition_price)) : ''}
                ${item.markup ? summaryRow('Markup', peso(item.markup)) : ''}
                ${item.seller_email ? summaryRow('Seller', item.seller_email) : ''}
            </div>
        `);
    } catch (error) {
        showOverlay(`
            <p style="font-size: 13px; color: var(--danger);">Could not load the item: ${escapeHtml(error.message)}</p>
            <button onclick="closeOverlay()" style="margin-top: 12px; background: var(--brand-600); color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer;">Close</button>
        `);
    }
}

async function sendMessage() {
    if (!selectedConversation) return;

    const messageField = document.getElementById('messageField');
    const message = messageField.value.trim();

    if (!message) return;

    try {
        const response = await fetch(`${API}/messages/${selectedConversation.item_id}`, {
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

/**
 * The same, for a value being written into a double-quoted HTML attribute.
 *
 * escapeHtml leaves quotes alone, which is fine between tags and not fine
 * inside one - a stray quote there ends the attribute early.
 */
function escapeAttr(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
</script>
@endpush
@endsection
