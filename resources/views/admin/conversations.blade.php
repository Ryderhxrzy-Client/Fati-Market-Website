@extends('layouts.admin-dashboard')

@section('title', 'Chat')
@section('subtitle', 'Buyers and sellers, in one place')

@section('content')
{{--
    The store's inbox, as the mobile app has it: pinned threads first, an
    Archived shelf, a name of your own for a thread, delete-for-me; replies
    with a quoted line (right-click a message), an emoji picker, and Enter to
    send. Order and offer cards keep their decisions.
--}}
<div class="chat-shell">
    <aside class="chat-list">
        <div class="chat-list-head">
            <span class="fm-search"><i class="fas fa-magnifying-glass"></i><input type="search" placeholder="Search people or items…" id="searchInput" class="fm-input"></span>
            <div class="chat-filters" id="chatFilters">
                <button class="chip on" data-filter="all">All</button>
                <button class="chip" data-filter="unread">Unread <span class="n" id="unreadChipCount"></span></button>
                <button class="chip" data-filter="negotiating">Negotiating</button>
                <button class="chip" data-filter="available">Available</button>
                <button class="chip" data-filter="reserved">Reserved</button>
                <button class="chip" data-filter="sold">Sold</button>
                <button class="chip" data-filter="rejected">Rejected</button>
                <button class="chip" data-filter="archived">Archived</button>
            </div>
        </div>
        <div id="conversationsList" class="chat-rows">
            <div class="fm-empty" style="padding: 40px 16px;"><span class="loading-spinner"></span><p style="margin-top: 10px;">Loading conversations…</p></div>
        </div>
    </aside>

    <section class="chat-pane">
        <div id="chatHeader" class="chat-head">
            <div class="chat-head-id">
                <div class="avatar" style="width: 40px; height: 40px;"><i class="fas fa-comments"></i></div>
                <div class="min-w-0">
                    <p class="cell-title" style="margin: 0;">Select a conversation</p>
                    <p class="cell-sub">Choose one from the list</p>
                </div>
            </div>
        </div>

        <!-- Pinned offer actions: buttons only, so a long thread cannot
             bury the decision that thread exists for. -->
        <div id="offerPinned" class="offer-pinned" style="display: none;"></div>

        <!-- The live order's decisions, pinned like the app's strip: one line
             saying which order and where it stands, then its buttons. -->
        <div id="orderPinned" class="order-pinned" style="display: none;"></div>

        <div id="messagesArea" class="chat-messages">
            <div class="fm-empty" style="padding: 60px 16px;">
                <i class="fas fa-comments"></i>
                <p>No conversation open</p>
                <span>Pick one on the left to read and reply.</span>
            </div>
        </div>

        <div id="composer" class="composer" hidden>
            <div id="replyBar" class="reply-bar" hidden>
                <div class="reply-bar-rule"></div>
                <div class="min-w-0" style="flex: 1;">
                    <p class="reply-bar-title" id="replyBarTitle"></p>
                    <p class="reply-bar-text" id="replyBarText"></p>
                </div>
                <button class="row-btn" onclick="clearReply()" aria-label="Cancel reply"><i class="fas fa-xmark"></i></button>
            </div>
            <div id="emojiPanel" class="emoji-panel" hidden>
                <div class="emoji-head">
                    <div class="emoji-tabs" id="emojiTabs"></div>
                    <button class="row-btn" onclick="closeEmoji()" aria-label="Close emoji picker" title="Close"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="emoji-grid" id="emojiGrid"></div>
            </div>
            <div class="composer-row">
                <button class="row-btn" id="emojiButton" onclick="toggleEmoji()" aria-label="Emoji" title="Emoji"><i class="fas fa-face-smile"></i></button>
                <textarea id="messageField" class="composer-input" rows="1" placeholder="Type a message… Enter to send, Shift+Enter for a new line"></textarea>
                <button class="send-btn" id="sendButton" onclick="sendMessage()" aria-label="Send" title="Send"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>

        <!-- Decision modal: replaces window.prompt / window.confirm -->
        <div id="actionModal" onclick="if (event.target === this) actionModalDone(false)" class="chat-overlay" style="z-index: 80;">
            <div id="actionModalBody" style="background: white; border-radius: 12px; max-width: 420px; width: 100%; padding: 20px; box-shadow: var(--shadow-lg);"></div>
        </div>

        <!-- Receipt lightbox / item panel -->
        <div id="chatOverlay" onclick="if (event.target === this) closeOverlay()" class="chat-overlay" style="z-index: 70;">
            <div id="chatOverlayBody" style="background: white; border-radius: 12px; max-width: 640px; width: 100%; max-height: 88vh; overflow-y: auto; padding: 24px;"></div>
        </div>
    </section>
</div>

<!-- Context menus: one for a message, one for a conversation row -->
<div id="ctxMenu" class="ctx-menu" hidden></div>

@include('admin.partials.meetup-picker')
@include('admin.partials.turnover')
@endsection

@push('styles')
<style>
    .chat-shell { display: flex; gap: 16px; height: calc(100vh - 64px - 48px); min-height: 480px; }
    .chat-list { width: 340px; flex-shrink: 0; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); display: flex; flex-direction: column; overflow: hidden; }
    .chat-list-head { padding: 12px 12px 8px; border-bottom: 1px solid var(--line); }
    .chat-filters { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
    .chip { padding: 5px 11px; border-radius: 999px; border: 1px solid var(--line-strong); background: var(--surface); color: var(--ink-700); font-size: 12px; font-weight: 600; cursor: pointer; display: inline-flex; gap: 5px; align-items: center; }
    .chip.on { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
    .chip .n:empty { display: none; }
    .chip .n { background: rgba(255,255,255,0.25); border-radius: 999px; padding: 0 6px; font-size: 11px; }
    .chip:not(.on) .n { background: var(--danger); color: #fff; }
    .chat-rows { flex: 1; overflow-y: auto; }
    .conv-row { display: flex; gap: 12px; padding: 12px 14px; cursor: pointer; border-bottom: 1px solid var(--line); position: relative; transition: background-color 0.12s ease; }
    .conv-row:hover { background: var(--surface-sunk); }
    .conv-row.active { background: var(--brand-50); }
    .conv-row.unread .conv-title { font-weight: 700; color: var(--ink-900); }
    .conv-avatar { position: relative; flex-shrink: 0; width: 48px; height: 48px; }
    .conv-avatar .avatar { width: 44px; height: 44px; }
    .conv-avatar .item-thumb { position: absolute; right: -2px; bottom: -2px; width: 22px; height: 22px; border-radius: 50%; border: 2px solid var(--surface); object-fit: cover; background: var(--surface-sunk); }
    .conv-body { flex: 1; min-width: 0; }
    .conv-top { display: flex; align-items: baseline; gap: 8px; }
    .conv-title { flex: 1; min-width: 0; font-size: 13.5px; font-weight: 600; color: var(--ink-800); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: flex; align-items: center; gap: 5px; }
    .conv-title .pin { color: var(--brand-600); font-size: 11px; }
    .conv-time { font-size: 11px; color: var(--ink-400); white-space: nowrap; }
    .conv-row.unread .conv-time { color: var(--brand-600); font-weight: 600; }
    .conv-sub { font-size: 12px; color: var(--ink-500); margin-top: 2px; display: flex; gap: 6px; align-items: center; white-space: nowrap; overflow: hidden; }
    .conv-sub .who { overflow: hidden; text-overflow: ellipsis; }
    .conv-last { font-size: 12.5px; color: var(--ink-500); margin-top: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .conv-row.unread .conv-last { color: var(--ink-800); font-weight: 500; }
    .conv-count { background: var(--danger); color: #fff; font-size: 10.5px; font-weight: 700; border-radius: 999px; min-width: 18px; height: 18px; padding: 0 5px; display: inline-flex; align-items: center; justify-content: center; }
    .conv-more { position: absolute; right: 8px; bottom: 8px; opacity: 0; }
    .conv-row:hover .conv-more { opacity: 1; }
    .section-label { padding: 8px 14px 4px; font-size: 10.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--ink-400); }

    .chat-pane { flex: 1; min-width: 0; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); display: flex; flex-direction: column; overflow: hidden; }
    .chat-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--line); flex-shrink: 0; }
    .chat-head-id { display: flex; align-items: center; gap: 12px; min-width: 0; flex: 1; }
    .chat-head .badges { display: flex; gap: 6px; align-items: center; margin-top: 3px; flex-wrap: wrap; }
    .offer-pinned { gap: 8px; flex-wrap: wrap; padding: 10px 16px; border-bottom: 1px solid var(--line); background: var(--surface-sunk); flex-shrink: 0; }
    .order-pinned { padding: 10px 16px; border-bottom: 1px solid var(--line); background: var(--brand-50); flex-shrink: 0; }
    .order-pinned .op-line { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 12.5px; color: var(--ink-700); }
    .order-pinned .op-line b { color: var(--ink-900); }
    .order-pinned .op-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
    .chat-messages { flex: 1; overflow-y: auto; padding: 16px 20px; background: var(--canvas); display: flex; flex-direction: column; gap: 4px; }

    .msg { display: flex; gap: 8px; align-items: flex-end; max-width: 72%; }
    .msg.me { align-self: flex-end; flex-direction: row-reverse; }
    .msg.them { align-self: flex-start; }
    .msg + .msg.same { margin-top: -2px; }
    .msg .avatar { width: 28px; height: 28px; font-size: 11px; }
    /* Children keep their own width: a long sender name must not stretch the bubble. */
    .msg .stack { display: flex; flex-direction: column; align-items: flex-start; min-width: 0; max-width: 100%; }
    .msg .stack > * { max-width: 100%; }
    .bubble { width: fit-content; }
    .msg.me .stack { align-items: flex-end; }
    .msg .sender { font-size: 11px; color: var(--ink-500); margin: 0 0 3px 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 240px; }
    .bubble { position: relative; padding: 8px 12px; border-radius: 18px; font-size: 13.5px; line-height: 1.4; word-break: break-word; cursor: context-menu; }
    .bubble .text { white-space: pre-wrap; }
    .msg.them .bubble { background: var(--surface); color: var(--ink-900); border-bottom-left-radius: 5px; box-shadow: var(--shadow-sm); }
    .msg.me .bubble { background: var(--brand-600); color: #fff; border-bottom-right-radius: 5px; }
    /* The quoted line sits above the bubble, the way Messenger draws a reply,
       and the bubble overlaps its bottom edge. */
    .reply-caption { font-size: 11px; color: var(--ink-400); margin: 0 6px 3px; display: flex; align-items: center; gap: 5px; }
    .quote-above { display: flex; gap: 8px; max-width: 100%; margin-bottom: -10px; padding: 6px 11px 15px; border-radius: 14px; background: var(--surface-sunk); border: 1px solid var(--line); font-size: 12px; color: var(--ink-600); }
    .msg.me .quote-above { background: var(--brand-50); border-color: var(--brand-100); }
    .quote-above .rule { width: 3px; border-radius: 3px; background: var(--brand-600); flex-shrink: 0; }
    .quote-above .q-name { font-weight: 700; margin: 0; color: var(--ink-700); }
    .quote-above .q-text { margin: 1px 0 0; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .msg .time { font-size: 10.5px; color: var(--ink-400); margin: 3px 4px 0; }
    .msg.me .time { text-align: right; }
    .msg .line { display: flex; align-items: center; gap: 4px; max-width: 100%; }
    .msg.me .line { flex-direction: row-reverse; }
    .msg .reply-hint { opacity: 0; transition: opacity 0.15s ease; flex-shrink: 0; width: 28px; height: 28px; }
    .msg:hover .reply-hint { opacity: 1; }
    .card { align-self: flex-start; max-width: 440px; width: 100%; }
    .card.me { align-self: flex-end; }
    .day-sep { align-self: center; font-size: 11px; color: var(--ink-400); background: var(--surface); padding: 3px 10px; border-radius: 999px; margin: 8px 0; box-shadow: var(--shadow-sm); }
    /* Something that happened to the thread itself - a rename - said in the middle, the way Messenger does. */
    .system-line { align-self: center; max-width: 80%; text-align: center; font-size: 12px; color: var(--ink-500); margin: 6px 0; display: flex; align-items: center; gap: 6px; }
    .system-line i { color: var(--brand-600); font-size: 11px; }
    .system-line b { color: var(--ink-700); font-weight: 600; }
    /* Where to collect the order, on the cards that reach the pickup stage. */
    .loc-block { margin-top: 12px; border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: var(--surface-sunk); }
    .loc-block iframe { display: block; width: 100%; height: 150px; border: 0; }
    .loc-block .loc-body { padding: 10px 12px; }
    .loc-block .loc-name { margin: 0; font-size: 13px; font-weight: 700; color: var(--ink-900); display: flex; align-items: center; gap: 6px; }
    .loc-block .loc-addr { margin: 2px 0 8px; font-size: 12px; color: var(--ink-600); }
    .loc-block .loc-actions { display: flex; gap: 6px; flex-wrap: wrap; }

    .composer { border-top: 1px solid var(--line); background: var(--surface); flex-shrink: 0; }
    .composer-row { display: flex; align-items: flex-end; gap: 8px; padding: 10px 12px; }
    .composer-input { flex: 1; resize: none; max-height: 120px; padding: 10px 14px; border: 1px solid var(--line-strong); border-radius: 20px; font-size: 13.5px; font-family: inherit; line-height: 1.4; background: var(--surface-sunk); }
    .composer-input:focus { outline: none; border-color: var(--brand-500); background: var(--surface); box-shadow: 0 0 0 3px var(--brand-100); }
    .send-btn { width: 40px; height: 40px; border-radius: 50%; border: none; background: var(--brand-600); color: #fff; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .send-btn:disabled { opacity: 0.5; cursor: not-allowed; }
    .reply-bar { display: flex; align-items: center; gap: 10px; padding: 8px 12px 0 16px; }
    .reply-bar-rule { width: 3px; height: 34px; border-radius: 3px; background: var(--brand-600); flex-shrink: 0; }
    .reply-bar-title { margin: 0; font-size: 12px; font-weight: 700; color: var(--brand-700); }
    .reply-bar-text { margin: 1px 0 0; font-size: 12px; color: var(--ink-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .emoji-panel { border-bottom: 1px solid var(--line); }
    .emoji-head { display: flex; align-items: center; gap: 6px; padding: 6px 8px 0 6px; }
    .emoji-tabs { display: flex; gap: 4px; padding: 2px 4px 0; overflow-x: auto; flex: 1; min-width: 0; }
    .emoji-tabs button { border: none; background: none; padding: 6px 10px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--ink-600); cursor: pointer; white-space: nowrap; }
    .emoji-tabs button.on { background: var(--brand-100); color: var(--brand-800); }
    .emoji-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(36px, 1fr)); gap: 2px; padding: 8px 10px; height: 200px; overflow-y: auto; }
    .emoji-grid button { border: none; background: none; font-size: 22px; line-height: 1; padding: 5px 0; border-radius: 8px; cursor: pointer; }
    .emoji-grid button:hover { background: var(--surface-sunk); }
    .emoji-grid .note { grid-column: 1 / -1; font-size: 12px; color: var(--ink-500); padding: 20px; text-align: center; }

    .ctx-menu { position: fixed; z-index: 90; min-width: 200px; background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius); box-shadow: var(--shadow-lg); padding: 6px; }
    .ctx-menu button { display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border-radius: var(--radius-sm); font-size: 13px; color: var(--ink-800); background: none; border: none; cursor: pointer; text-align: left; }
    .ctx-menu button:hover { background: var(--surface-sunk); }
    .ctx-menu button i { width: 15px; color: var(--ink-500); }
    .ctx-menu button.danger, .ctx-menu button.danger i { color: var(--danger); }
    .ctx-menu .title { padding: 6px 10px 8px; font-size: 11.5px; color: var(--ink-500); border-bottom: 1px solid var(--line); margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px; }

    .chat-overlay { display: none; position: fixed; inset: 0; background: rgba(12, 48, 33, 0.55); align-items: center; justify-content: center; padding: 24px; }

    @media (max-width: 900px) {
        .chat-shell { flex-direction: column; height: auto; }
        .chat-list { width: 100%; max-height: 40vh; }
        .chat-pane { min-height: 60vh; }
        .msg { max-width: 90%; }
    }
</style>
@endpush

@push('scripts')
<script>
const API = 'https://fati-api.alertaraqc.com/api';

let token = null;
let selectedConversation = null;
let allConversations = [];
let currentMessages = [];
// The lines the thread keeps about itself - a rename so far - as the server
// hands them over. They used to live in this browser's storage, so renaming
// on the phone showed nothing here and renaming here showed nothing there.
let currentEvents = [];
let busyAction = false;
let listFilter = 'all';
let replyTarget = null;
let listPoll = null;
let threadPoll = null;

// Item-status badge colours. An unknown status falls back to grey instead of
// `undefined`, which used to paint a white badge with white text.
const statusTone = {
    public: 'success', private: '', pending: 'warning', acquired: 'brand', reserved: 'info', sold: '', rejected: 'danger',
};
const statusLabel = {
    public: 'Available', private: 'Negotiating', pending: 'Negotiating', acquired: 'Acquired', reserved: 'Reserved', sold: 'Sold', rejected: 'Rejected',
};

function getToken() {
    const metaToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content');
    if (metaToken && metaToken.trim()) return metaToken;
    return sessionStorage.getItem('admin_token') || localStorage.getItem('admin_token') || sessionStorage.getItem('token') || localStorage.getItem('token');
}

function authHeaders(json) {
    const headers = { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' };
    if (json) headers['Content-Type'] = 'application/json';
    return headers;
}

function convKey(conv) { return `${conv.item_id}_${conv.other_user_id}`; }

/** A readable, reload-proof address for a thread: ids first, then the names for people. */
function threadSlug(conv) {
    const words = `${personName(conv)} ${convName(conv)}`.toLowerCase().normalize('NFD').replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').slice(0, 60);
    return `${conv.item_id}-${conv.other_user_id}${words ? '-' + words : ''}`;
}

function threadFromUrl() {
    const value = new URLSearchParams(window.location.search).get('thread') || '';
    const match = value.match(/^(\d+)-(\d+)/);
    return match ? `${match[1]}_${match[2]}` : null;
}

function writeThreadUrl(conv) {
    const url = new URL(window.location.href);
    if (conv) url.searchParams.set('thread', threadSlug(conv)); else url.searchParams.delete('thread');
    history.replaceState(null, '', url.pathname + (url.search || '') + url.hash);
}
function convName(conv) { return conv.custom_name || conv.item_title || 'Conversation'; }
function personName(conv) {
    const email = conv.other_user_email || '';
    return conv.first_name && conv.last_name ? `${conv.first_name} ${conv.last_name}` : (conv.first_name || conv.last_name || email.split('@')[0] || 'User');
}
function initials(name) { return name.split(' ').filter(Boolean).map(n => n[0]).join('').toUpperCase().slice(0, 2) || '?'; }
function itemBadge(status) {
    const key = String(status || '').toLowerCase();
    return `<span class="fm-badge ${statusTone[key] ?? ''}">${escapeHtml(statusLabel[key] || key || 'Item')}</span>`;
}

document.addEventListener('DOMContentLoaded', async function() {
    token = getToken();

    if (!token) {
        document.getElementById('messagesArea').innerHTML = `
            <div class="fm-empty"><i class="fas fa-lock"></i><p>Authentication required</p><span>Please sign in again.</span></div>`;
        return;
    }

    document.querySelectorAll('#chatFilters .chip').forEach(chip => chip.addEventListener('click', () => {
        listFilter = chip.dataset.filter;
        document.querySelectorAll('#chatFilters .chip').forEach(c => c.classList.toggle('on', c === chip));
        renderConversations();
    }));

    const field = document.getElementById('messageField');
    field.addEventListener('keydown', function (event) {
        // Enter sends; Shift+Enter is a new line, as in Messenger.
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            sendMessage();
        }
        if (event.key === 'Escape' && replyTarget) clearReply();
    });
    field.addEventListener('input', autosize);

    document.addEventListener('click', (e) => {
        hideCtxMenu();
        // A click anywhere outside the picker or its button closes it.
        if (!e.target.closest('#emojiPanel') && !e.target.closest('#emojiButton')) closeEmoji();
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { hideCtxMenu(); closeEmoji(); } });
    window.addEventListener('scroll', hideCtxMenu, true);

    await loadConversations();
    // Reopen the thread named in the address, so a reload lands where it was
    // and so the offers page can send someone straight to a seller.
    const wanted = threadFromUrl();
    if (wanted) {
        if (findConversation(wanted)) {
            openConversation(wanted);
        } else {
            // Better than opening the inbox in silence and looking broken.
            showToast('That conversation is not in your list. It may have been deleted for you.', 'error');
        }
    }
    // The list refreshes on its own, like the app's; the open thread too.
    listPoll = setInterval(() => loadConversations(true), 15000);
});

function autosize() {
    const field = document.getElementById('messageField');
    field.style.height = 'auto';
    field.style.height = Math.min(field.scrollHeight, 120) + 'px';
}

// ── Conversations ────────────────────────────────────────────────────────

async function loadConversations(quiet) {
    try {
        const response = await fetch(`${API}/conversations`, { headers: authHeaders() });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        allConversations = Array.isArray(data) ? data : (data.data || data.conversations || []);
        renderConversations();
    } catch (error) {
        if (quiet) return;
        document.getElementById('conversationsList').innerHTML = `
            <div class="fm-empty"><i class="fas fa-cloud-slash"></i><p>Could not load conversations</p><span>${escapeHtml(error.message)}</span>
            <div class="mt-4"><button class="fm-btn primary sm" onclick="loadConversations()">Try again</button></div></div>`;
    }
}

function visibleConversations() {
    const query = (document.getElementById('searchInput').value || '').trim().toLowerCase();
    return allConversations.filter(conv => {
        const archived = !!conv.is_archived;
        if (listFilter === 'archived' ? !archived : archived) return false;
        if (listFilter === 'unread' && !(Number(conv.unread_count) > 0)) return false;
        // The item's stage, in the words the list already uses for its badge.
        const stage = { pending: 'negotiating', private: 'negotiating', public: 'available', reserved: 'reserved', sold: 'sold', rejected: 'rejected' }[String(conv.item_status || '').toLowerCase()] || '';
        if (['negotiating', 'available', 'reserved', 'sold', 'rejected'].includes(listFilter) && stage !== listFilter) return false;
        if (!query) return true;
        return [personName(conv), conv.item_title, conv.custom_name, conv.latest_message].join(' ').toLowerCase().includes(query);
    });
}

function renderConversations() {
    const list = document.getElementById('conversationsList');
    const unread = allConversations.filter(c => !c.is_archived).reduce((n, c) => n + (Number(c.unread_count) || 0), 0);
    document.getElementById('unreadChipCount').textContent = unread > 0 ? String(unread) : '';

    const rows = visibleConversations();

    if (rows.length === 0) {
        const message = listFilter === 'archived' ? ['Nothing archived', 'Right-click a conversation to archive it.']
            : listFilter === 'unread' ? ['All caught up', 'You have read every message.']
            : listFilter !== 'all' ? [`No ${listFilter} items`,'No conversation is about an item in that stage right now.']
            : ['No conversations', 'Chats open when a student offers an item or asks about a listing.'];
        list.innerHTML = `<div class="fm-empty"><i class="fas fa-inbox"></i><p>${message[0]}</p><span>${message[1]}</span></div>`;
        return;
    }

    const pinned = rows.filter(c => c.is_pinned);
    const rest = rows.filter(c => !c.is_pinned);
    const section = (label) => `<div class="section-label">${label}</div>`;

    list.innerHTML = (pinned.length ? section('Pinned') + pinned.map(rowHtml).join('') : '')
        + (pinned.length && rest.length ? section(listFilter === 'archived' ? 'Archived' : 'Recent') : '')
        + rest.map(rowHtml).join('');

    list.querySelectorAll('.conv-row').forEach(row => {
        row.addEventListener('click', () => openConversation(row.dataset.key));
        row.addEventListener('contextmenu', (event) => {
            event.preventDefault();
            showConversationMenu(event.clientX, event.clientY, row.dataset.key);
        });
        row.querySelector('.conv-more').addEventListener('click', (event) => {
            event.stopPropagation();
            const rect = event.currentTarget.getBoundingClientRect();
            showConversationMenu(rect.left, rect.bottom + 4, row.dataset.key);
        });
    });
}

function rowHtml(conv) {
    const name = personName(conv);
    const unreadCount = Number(conv.unread_count) || 0;
    const key = convKey(conv);
    const active = selectedConversation && convKey(selectedConversation) === key;
    const lastMessage = conv.latest_message || 'No messages yet';

    return `
        <div class="conv-row ${unreadCount > 0 ? 'unread' : ''} ${active ? 'active' : ''}" data-key="${escapeAttr(key)}">
            <div class="conv-avatar">
                ${conv.profile_picture
                    ? `<img src="${escapeAttr(conv.profile_picture)}" alt="" class="avatar">`
                    : `<div class="avatar">${escapeHtml(initials(name))}</div>`}
                ${conv.item_photo ? `<img src="${escapeAttr(conv.item_photo)}" alt="" class="item-thumb">` : ''}
            </div>
            <div class="conv-body">
                <div class="conv-top">
                    <span class="conv-title">${conv.is_pinned ? '<i class="fas fa-thumbtack pin"></i>' : ''}<span style="overflow: hidden; text-overflow: ellipsis;">${escapeHtml(convName(conv))}</span></span>
                    <span class="conv-time">${escapeHtml(timeAgo(conv.last_message_at))}</span>
                </div>
                <div class="conv-sub">
                    <span class="who">${escapeHtml(name)}</span>
                    ${itemBadge(conv.item_status)}
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span class="conv-last" style="flex: 1;">${escapeHtml(lastMessage)}</span>
                    ${unreadCount > 0 ? `<span class="conv-count">${unreadCount > 99 ? '99+' : unreadCount}</span>` : ''}
                </div>
            </div>
            <button class="row-btn conv-more" aria-label="Options"><i class="fas fa-ellipsis"></i></button>
        </div>`;
}

function findConversation(key) {
    return allConversations.find(c => convKey(c) === key) || null;
}

function timeAgo(value) {
    if (!value) return '';
    const date = new Date(value);
    if (isNaN(date.getTime())) return '';
    const seconds = Math.round((Date.now() - date.getTime()) / 1000);
    if (seconds < 60) return 'now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h`;
    if (seconds < 7 * 86400) return `${Math.floor(seconds / 86400)}d`;
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
}

// ── Conversation housekeeping: pin, rename, archive, delete ──────────────

function showConversationMenu(x, y, key) {
    const conv = findConversation(key);
    if (!conv) return;

    showCtxMenu(x, y, `
        <div class="title">${escapeHtml(convName(conv))} &middot; ${escapeHtml(personName(conv))}</div>
        <button onclick="conversationAction('${escapeAttr(key)}', 'pin')"><i class="fas fa-thumbtack"></i>${conv.is_pinned ? 'Unpin' : 'Pin to top'}</button>
        <button onclick="conversationAction('${escapeAttr(key)}', 'rename')"><i class="fas fa-pen"></i>Rename conversation</button>
        <button onclick="conversationAction('${escapeAttr(key)}', 'archive')"><i class="fas fa-box-archive"></i>${conv.is_archived ? 'Unarchive' : 'Archive'}</button>
        <button class="danger" onclick="conversationAction('${escapeAttr(key)}', 'delete')"><i class="fas fa-trash"></i>Delete conversation</button>
    `);
}

async function conversationAction(key, action) {
    hideCtxMenu();
    const conv = findConversation(key);
    if (!conv) return;

    if (action === 'rename') {
        const name = await askModal({
            title: 'Rename conversation',
            body: 'Only you see this name. Leave it blank to use the item\'s title again.',
            field: { label: 'Name', type: 'text', value: conv.custom_name || '', placeholder: conv.item_title || '' },
            confirmLabel: 'Save',
        });
        if (name === null) return;
        const cleaned = name.trim().slice(0, 80);
        if (cleaned === (conv.custom_name || '')) return;
        const result = await patchConversation(conv, { custom_name: cleaned });
        // The line is written by the server, so the open thread is re-read
        // rather than guessed at.
        if (selectedConversation && convKey(selectedConversation) === key) await loadThread();
        return result;
    }

    if (action === 'pin') return patchConversation(conv, { is_pinned: !conv.is_pinned });
    if (action === 'archive') return patchConversation(conv, { is_archived: !conv.is_archived });

    if (action === 'delete') {
        const confirmed = await askModal({
            title: 'Delete this conversation?',
            body: `"${convName(conv)}" is cleared from your list. ${personName(conv)} keeps their copy, and the chat comes back here if either of you writes again.`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (confirmed === null) return;

        try {
            const response = await fetch(`${API}/conversations/${conv.item_id}/${conv.other_user_id}`, { method: 'DELETE', headers: authHeaders() });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            allConversations = allConversations.filter(c => convKey(c) !== key);
            if (selectedConversation && convKey(selectedConversation) === key) closeThread();
            renderConversations();
            showToast('Conversation deleted', 'success');
        } catch (error) {
            showToast(`Could not delete: ${error.message}`, 'error');
        }
    }
}

async function patchConversation(conv, patch) {
    // Optimistic: the row moves at once, and only moves back if the server refused.
    const before = { ...conv };
    Object.assign(conv, patch);
    if (patch.is_archived === true) conv.is_pinned = false;
    if (patch.custom_name !== undefined) conv.custom_name = patch.custom_name || null;
    // Archiving from the inbox: jump to the Archived shelf so the row is seen landing there.
    if (patch.is_archived === true && listFilter !== 'archived') {
        listFilter = 'archived';
        document.querySelectorAll('#chatFilters .chip').forEach(c => c.classList.toggle('on', c.dataset.filter === 'archived'));
    }
    if (patch.is_archived === false && listFilter === 'archived') {
        listFilter = 'all';
        document.querySelectorAll('#chatFilters .chip').forEach(c => c.classList.toggle('on', c.dataset.filter === 'all'));
    }
    renderConversations();
    if (selectedConversation && convKey(selectedConversation) === convKey(conv)) renderHeader();

    try {
        const response = await fetch(`${API}/conversations/${conv.item_id}/${conv.other_user_id}`, {
            method: 'PATCH', headers: authHeaders(true), body: JSON.stringify(patch),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        Object.assign(conv, payload.data || {});
        renderConversations();
        if (selectedConversation && convKey(selectedConversation) === convKey(conv)) writeThreadUrl(conv);
        showToast(patch.custom_name !== undefined ? 'Conversation renamed'
            : patch.is_pinned !== undefined ? (conv.is_pinned ? 'Pinned' : 'Unpinned')
            : (conv.is_archived ? 'Archived - find it under the Archived tab' : 'Back in the inbox'), 'success');
    } catch (error) {
        Object.assign(conv, before);
        renderConversations();
        if (selectedConversation && convKey(selectedConversation) === convKey(conv)) renderHeader();
        showToast(`Could not update: ${error.message}${/404/.test(error.message) ? ' (the API does not have the chat update deployed yet)' : ''}`, 'error');
    }
}

// ── The thread ───────────────────────────────────────────────────────────

function closeThread() {
    selectedConversation = null;
    writeThreadUrl(null);
    renderPinnedOrder(null);
    currentMessages = [];
    currentEvents = [];
    clearReply();
    if (threadPoll) clearInterval(threadPoll);
    document.getElementById('composer').hidden = true;
    document.getElementById('offerPinned').style.display = 'none';
    document.getElementById('chatHeader').innerHTML = `
        <div class="chat-head-id">
            <div class="avatar" style="width: 40px; height: 40px;"><i class="fas fa-comments"></i></div>
            <div class="min-w-0"><p class="cell-title" style="margin: 0;">Select a conversation</p><p class="cell-sub">Choose one from the list</p></div>
        </div>`;
    document.getElementById('messagesArea').innerHTML = `
        <div class="fm-empty" style="padding: 60px 16px;"><i class="fas fa-comments"></i><p>No conversation open</p><span>Pick one on the left to read and reply.</span></div>`;
}

async function openConversation(key) {
    const conv = findConversation(key);
    if (!conv) return;

    selectedConversation = conv;
    writeThreadUrl(conv);
    clearReply();
    renderConversations();
    renderHeader();
    document.getElementById('composer').hidden = false;
    document.getElementById('messagesArea').innerHTML = '<div class="fm-empty" style="padding: 40px;"><span class="loading-spinner"></span></div>';

    await loadThread();
    markThreadRead(conv.item_id);
    if (threadPoll) clearInterval(threadPoll);
    threadPoll = setInterval(() => loadThread(true), 5000);
    document.getElementById('messageField').focus();
}

function renderHeader() {
    const conv = selectedConversation;
    if (!conv) return;
    const name = personName(conv);
    const custom = conv.custom_name;

    document.getElementById('chatHeader').innerHTML = `
        <div class="chat-head-id">
            ${conv.profile_picture
                ? `<img src="${escapeAttr(conv.profile_picture)}" alt="" class="avatar" style="width: 42px; height: 42px;">`
                : `<div class="avatar" style="width: 42px; height: 42px;">${escapeHtml(initials(name))}</div>`}
            <div class="min-w-0" style="flex: 1;">
                <p class="cell-title" style="margin: 0; display: flex; align-items: center; gap: 6px;">
                    ${conv.is_pinned ? '<i class="fas fa-thumbtack" style="color: var(--brand-600); font-size: 11px;"></i>' : ''}
                    <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(custom || name)}</span>
                </p>
                <div class="badges">
                    <span class="cell-sub">${escapeHtml(custom ? `${name} · ${conv.item_title || ''}` : (conv.item_title || 'Item'))}</span>
                    ${itemBadge(conv.item_status)}
                    ${conv.is_archived ? '<span class="fm-badge">Archived</span>' : ''}
                </div>
            </div>
        </div>
        <div style="display: flex; gap: 6px; align-items: center;">
            ${conv.item_photo ? `<img src="${escapeAttr(conv.item_photo)}" alt="" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover; cursor: pointer;" onclick="openItem(${Number(conv.item_id)})">` : ''}
            <button class="icon-btn" title="Options" aria-label="Conversation options" onclick="event.stopPropagation(); const r = this.getBoundingClientRect(); showConversationMenu(r.right - 200, r.bottom + 4, '${escapeAttr(convKey(conv))}')"><i class="fas fa-ellipsis-vertical"></i></button>
        </div>`;
}

async function loadThread(quiet) {
    const conv = selectedConversation;
    if (!conv) return;

    try {
        const response = await fetch(`${API}/messages/${conv.item_id}?other_user_id=${conv.other_user_id}`, { headers: authHeaders() });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        const messages = Array.isArray(data) ? data : (data.data || data.messages || []);
        const events = Array.isArray(data) ? [] : (data.events || []);
        if (selectedConversation !== conv) return;

        const changed = JSON.stringify(messages.map(m => [m.message_id, m.order?.status, m.order?.payment_status, m.item_card?.status]))
            !== JSON.stringify(currentMessages.map(m => [m.message_id, m.order?.status, m.order?.payment_status, m.item_card?.status]));
        const eventsChanged = JSON.stringify(events) !== JSON.stringify(currentEvents);
        currentMessages = messages;
        currentEvents = events;
        if (!quiet || changed || eventsChanged) renderMessages(messages);
    } catch (error) {
        if (quiet) return;
        document.getElementById('messagesArea').innerHTML = `
            <div class="fm-empty"><i class="fas fa-cloud-slash"></i><p>Could not load messages</p><span>${escapeHtml(error.message)}</span>
            <div class="mt-4"><button class="fm-btn primary sm" onclick="loadThread()">Try again</button></div></div>`;
    }
}

/**
 * Reading a thread clears its unread count, the same call the phone makes
 * when a chat is opened. The sidebar badge follows.
 */
async function markThreadRead(itemId) {
    try {
        await fetch(`${API}/messages/${itemId}/read`, { method: 'POST', headers: authHeaders(true), body: '{}' });
        const conv = allConversations.find(c => String(c.item_id) === String(itemId) && selectedConversation && c.other_user_id === selectedConversation.other_user_id);
        if (conv) conv.unread_count = 0;
        renderConversations();
        if (typeof window.fmRefreshUnread === 'function') window.fmRefreshUnread();
    } catch (error) {
        // Not fatal: the badge catches up on the next poll.
    }
}

function renderMessages(messages) {
    const area = document.getElementById('messagesArea');
    const stuckToBottom = area.scrollHeight - area.scrollTop - area.clientHeight < 80;

    // Pin the offer's buttons under the header, so scrolling cannot lose
    // them. The newest item_listed message carries the live listing.
    const offerMsg = [...messages].reverse().find((m) => m.kind === 'item_listed' && m.item_card);
    renderPinnedOffer(offerMsg ? offerMsg.item_card : null);

    if (messages.length === 0) {
        area.innerHTML = `<div class="fm-empty" style="padding: 60px 16px;"><i class="fas fa-comments"></i><p>Say hello</p><span>Start the conversation about this item.</span></div>`;
        return;
    }

    // The strip above the thread carries the newest order's open decisions.
    const liveOrderMsg = [...messages].reverse().find(m => m.kind && m.kind !== 'text' && m.order && carriesActions(m));
    renderPinnedOrder(liveOrderMsg ? liveOrderMsg.order : null);

    const pickupCards = messages.filter(m => m.kind && m.kind !== 'text' && m.order && pickupStageOf(m));
    latestPickupMessageId = pickupCards.length ? pickupCards[pickupCards.length - 1].message_id : null;

    // Renames are this person's own, and the server keeps them for the
    // account rather than for the browser, so they show up on every device
    // this admin signs in on. Each sits at the moment it happened.
    const stampOf = (value) => {
        const at = new Date(String(value || '').replace(' ', 'T')).getTime();
        return isNaN(at) ? Number.MAX_SAFE_INTEGER : at;
    };

    const rows = [
        ...messages.map(msg => ({ at: stampOf(msg.sent_at), render: () => renderOne(msg) })),
        ...threadEvents().map(event => ({ at: stampOf(event.at), render: () => systemLineHtml(event) })),
    ].sort((a, b) => a.at - b.at);

    let lastDay = '';
    let lastSender = null;
    area.innerHTML = rows.map(row => row.render()).join('');

    if (stuckToBottom || !area.dataset.scrolled) {
        area.scrollTop = area.scrollHeight;
        area.dataset.scrolled = '1';
    }

    function renderOne(msg) {
        const isAdmin = msg.sender_id !== selectedConversation.other_user_id;
        const senderName = msg.sender_name || 'User';
        const when = new Date(msg.sent_at);
        const timestamp = isNaN(when.getTime()) ? '' : when.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const day = isNaN(when.getTime()) ? '' : when.toDateString();
        let separator = '';
        if (day && day !== lastDay) {
            separator = `<div class="day-sep">${escapeHtml(dayLabel(when))}</div>`;
            lastDay = day;
            lastSender = null;
        }
        const same = lastSender === msg.sender_id;
        lastSender = msg.sender_id;

        if (msg.kind && msg.kind !== 'text' && msg.order) {
            return separator + renderOrderCard(msg, isAdmin, senderName, timestamp);
        }
        if (msg.kind === 'item_listed' && msg.item_card) {
            return separator + renderItemOfferCard(msg, isAdmin, senderName, timestamp);
        }

        return separator + `
            <div class="msg ${isAdmin ? 'me' : 'them'} ${same ? 'same' : ''}" data-id="${Number(msg.message_id)}">
                ${isAdmin ? '' : (msg.sender_profile_picture
                    ? `<img src="${escapeAttr(msg.sender_profile_picture)}" alt="" class="avatar" style="${same ? 'visibility: hidden;' : ''}">`
                    : `<div class="avatar" style="${same ? 'visibility: hidden;' : ''}">${escapeHtml(initials(senderName))}</div>`)}
                <div class="stack">
                    ${!isAdmin && !same ? `<p class="sender">${escapeHtml(senderName)}</p>` : ''}
                    ${msg.reply_to ? quoteHtml(msg.reply_to, isAdmin) : ''}<div class="line"><div class="bubble" oncontextmenu="showMessageMenu(event, ${Number(msg.message_id)})"><span class="text">${escapeHtml(msg.message)}</span></div><button class="row-btn reply-hint" title="Reply" onclick="startReply(${Number(msg.message_id)})"><i class="fas fa-reply"></i></button></div>
                    <p class="time" title="${escapeAttr(msg.sent_at || '')}">${escapeHtml(timestamp)}</p>
                </div>
            </div>`;
    }
}

/** Buttons only, for the order the thread is about; the card tells the whole story. */
function renderPinnedOrder(order) {
    const bar = document.getElementById('orderPinned');
    const actions = order ? (order.available_actions || []) : [];

    if (!order || actions.length === 0) { bar.style.display = 'none'; bar.innerHTML = ''; return; }

    bar.innerHTML = `
        <div class="op-line">
            <i class="fas fa-receipt" style="color: var(--brand-600);"></i>
            <b>${escapeHtml(order.receipt_no || ('Order #' + order.transaction_id))}</b>
            <span>&middot; ${peso(order.amount_due)} &middot; ${escapeHtml(paymentMethodLabel(order.payment_method))}</span>
            ${paymentStateBadge(order)}
            ${orderStatusBadge(order.status, order.payment_method)}
        </div>
        <div class="op-actions">${orderActionsHtml(order).replace('border-top: 1px solid var(--surface-sunk); padding-top: 12px;', '').replace('margin-top: 12px;', '')}</div>`;
    bar.style.display = 'block';
}

function systemLineHtml(event) {
    // The API sends "2026-09-16 18:11:00"; Safari refuses that without the T.
    const when = new Date(String(event.at || '').replace(' ', 'T'));
    const stamp = isNaN(when.getTime()) ? '' : ' · ' + when.toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    const text = event.name
        ? `You renamed the conversation to <b>${escapeHtml(event.name)}</b>`
        : 'You removed the conversation name';
    return `<div class="system-line"><i class="fas fa-pen"></i><span>${text}${escapeHtml(stamp)}</span></div>`;
}

function threadEvents() {
    return currentEvents || [];
}

function dayLabel(date) {
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const that = new Date(date); that.setHours(0, 0, 0, 0);
    const diff = Math.round((today - that) / 86400000);
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Yesterday';
    return date.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
}

function quoteHtml(quote, mine) {
    const other = personName(selectedConversation);
    const quotedTheirs = quote.sender_id === selectedConversation.other_user_id;
    const quotedName = quotedTheirs ? (quote.sender_name || other) : 'You';
    const caption = `${mine ? 'You' : other} replied to ${quotedTheirs ? (mine ? quotedName : 'themselves') : (mine ? 'yourself' : 'you')}`;
    return `<p class="reply-caption"><i class="fas fa-reply" style="font-size: 10px;"></i>${escapeHtml(caption)}</p>`
        + `<div class="quote-above"><div class="rule"></div><div class="min-w-0"><p class="q-name">${escapeHtml(quotedName)}</p><p class="q-text">${escapeHtml(quote.message || quote.kind || '')}</p></div></div>`;
}

// ── Replies ──────────────────────────────────────────────────────────────

function previewOf(msg) {
    if (msg.kind && msg.kind !== 'text' && msg.order) return { order_placed: 'Order placed', payment_submitted: 'Payment sent', order_update: 'Order update' }[msg.kind] || 'Order card';
    if (msg.kind === 'item_listed') return 'Item offer';
    if (msg.kind === 'item_acquired') return 'Item received';
    return msg.message || '';
}

function showMessageMenu(event, messageId) {
    event.preventDefault();
    const msg = currentMessages.find(m => Number(m.message_id) === Number(messageId));
    if (!msg) return;

    showCtxMenu(event.clientX, event.clientY, `
        <button onclick="startReply(${Number(messageId)})"><i class="fas fa-reply"></i>Reply</button>
        <button onclick="copyMessage(${Number(messageId)})"><i class="fas fa-copy"></i>Copy text</button>
    `);
}

function startReply(messageId) {
    hideCtxMenu();
    const msg = currentMessages.find(m => Number(m.message_id) === Number(messageId));
    if (!msg) return;

    replyTarget = msg;
    const mine = msg.sender_id !== selectedConversation.other_user_id;
    document.getElementById('replyBarTitle').textContent = `Replying to ${mine ? 'yourself' : (msg.sender_name || personName(selectedConversation))}`;
    document.getElementById('replyBarText').textContent = previewOf(msg);
    document.getElementById('replyBar').hidden = false;
    document.getElementById('messageField').focus();
}

function clearReply() {
    replyTarget = null;
    const bar = document.getElementById('replyBar');
    if (bar) bar.hidden = true;
}

function copyMessage(messageId) {
    hideCtxMenu();
    const msg = currentMessages.find(m => Number(m.message_id) === Number(messageId));
    if (!msg) return;
    const text = previewOf(msg);
    if (navigator.clipboard?.writeText) navigator.clipboard.writeText(text).then(() => showToast('Copied', 'success'));
}

function showCtxMenu(x, y, html) {
    const menu = document.getElementById('ctxMenu');
    menu.innerHTML = html;
    menu.hidden = false;
    const width = menu.offsetWidth, height = menu.offsetHeight;
    menu.style.left = Math.min(x, window.innerWidth - width - 8) + 'px';
    menu.style.top = Math.min(y, window.innerHeight - height - 8) + 'px';
}

function hideCtxMenu() {
    const menu = document.getElementById('ctxMenu');
    if (menu) menu.hidden = true;
}

// ── Emoji ────────────────────────────────────────────────────────────────
// EmojiHub, the same free API the mobile app uses; a CDN copy of the Unicode
// emoji list stands in if it cannot be reached. Both are cached per session.

const EMOJI_CATEGORIES = [
    ['Smileys', 'smileys-and-people'], ['Animals', 'animals-and-nature'], ['Food', 'food-and-drink'],
    ['Travel', 'travel-and-places'], ['Activities', 'activities'], ['Objects', 'objects'], ['Symbols', 'symbols'], ['Flags', 'flags'],
];
const emojiCache = {};
let emojiTab = 'smileys-and-people';

function toggleEmoji() {
    const panel = document.getElementById('emojiPanel');
    if (!panel.hidden) return closeEmoji();
    panel.hidden = false;
    document.getElementById('emojiButton').classList.add('on');
    renderEmojiTabs();
    loadEmojis(emojiTab);
}

function closeEmoji() {
    const panel = document.getElementById('emojiPanel');
    if (!panel || panel.hidden) return;
    panel.hidden = true;
    document.getElementById('emojiButton').classList.remove('on');
    document.getElementById('messageField').focus();
}

function renderEmojiTabs() {
    document.getElementById('emojiTabs').innerHTML = EMOJI_CATEGORIES.map(([label, slug]) =>
        `<button class="${slug === emojiTab ? 'on' : ''}" onclick="emojiTab='${slug}'; renderEmojiTabs(); loadEmojis('${slug}')">${label}</button>`).join('');
}

function htmlCodeToChar(code) {
    const point = Number(String(code).replace('&#', '').replace(';', ''));
    return Number.isFinite(point) ? String.fromCodePoint(point) : '';
}

async function loadEmojis(slug) {
    const grid = document.getElementById('emojiGrid');
    if (emojiCache[slug]) return renderEmojis(emojiCache[slug]);

    const stored = sessionStorage.getItem('fm_emoji_' + slug);
    if (stored) { emojiCache[slug] = JSON.parse(stored); return renderEmojis(emojiCache[slug]); }

    grid.innerHTML = '<div class="note"><span class="loading-spinner"></span></div>';
    let chars = [];
    try {
        const response = await fetch(`https://emojihub.yurace.pro/api/all/category/${slug}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const list = await response.json();
        chars = list.map(e => (e.htmlCode || []).map(htmlCodeToChar).join('')).filter(Boolean);
    } catch (error) {
        try {
            const fallback = await fetch('https://cdn.jsdelivr.net/npm/unicode-emoji-json@0.6.0/data-by-group.json');
            const groups = await fallback.json();
            const wanted = { 'smileys-and-people': ['Smileys & Emotion', 'People & Body'], 'animals-and-nature': ['Animals & Nature'], 'food-and-drink': ['Food & Drink'],
                'travel-and-places': ['Travel & Places'], 'activities': ['Activities'], 'objects': ['Objects'], 'symbols': ['Symbols'], 'flags': ['Flags'] }[slug] || [];
            const rows = Array.isArray(groups) ? groups.filter(g => wanted.includes(g.name)).flatMap(g => g.emojis || []) : wanted.flatMap(name => groups[name] || []);
            chars = rows.map(e => e.emoji).filter(Boolean);
        } catch (e) {
            grid.innerHTML = '<div class="note">Emoji could not be loaded right now.</div>';
            return;
        }
    }
    emojiCache[slug] = chars;
    try { sessionStorage.setItem('fm_emoji_' + slug, JSON.stringify(chars)); } catch (e) {}
    renderEmojis(chars);
}

function renderEmojis(chars) {
    document.getElementById('emojiGrid').innerHTML = chars.slice(0, 400).map(c =>
        `<button type="button" onclick="insertEmoji(this.textContent)">${c}</button>`).join('');
}

function insertEmoji(char) {
    const field = document.getElementById('messageField');
    const start = field.selectionStart ?? field.value.length;
    const end = field.selectionEnd ?? field.value.length;
    field.value = field.value.slice(0, start) + char + field.value.slice(end);
    field.selectionStart = field.selectionEnd = start + char.length;
    field.focus();
    autosize();
}

// ── Sending ──────────────────────────────────────────────────────────────

async function sendMessage() {
    if (!selectedConversation) return;

    const field = document.getElementById('messageField');
    const message = field.value.trim();
    if (!message) return;

    const button = document.getElementById('sendButton');
    button.disabled = true;
    const quoted = replyTarget;

    try {
        const body = { receiver_id: selectedConversation.other_user_id, message };
        if (quoted) body.reply_to_message_id = quoted.message_id;

        const response = await fetch(`${API}/messages/${selectedConversation.item_id}`, {
            method: 'POST', headers: authHeaders(true), body: JSON.stringify(body),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        field.value = '';
        autosize();
        clearReply();
        closeEmoji();
        await loadThread();
        loadConversations(true);
    } catch (error) {
        showToast('Could not send: ' + error.message, 'error');
    } finally {
        button.disabled = false;
        field.focus();
    }
}

// ── Order cards ──────────────────────────────────────────────────────────

const PESO = '₱';

// The same fixed pin the mobile app and the settings page show.
const STORE = {
    name: "Ofelia's Store",
    address: 'Hollywood Terraces, Sumulong Hwy, Antipolo, 1870, Rizal',
    lat: '14.619292',
    lng: '121.151418',
};
STORE.embed = `https://maps.google.com/maps?q=${STORE.lat},${STORE.lng}&z=16&output=embed`;
STORE.maps = `https://www.google.com/maps/search/?api=1&query=${STORE.lat},${STORE.lng}`;
STORE.directions = `https://www.google.com/maps/dir/?api=1&destination=${STORE.lat},${STORE.lng}`;

/** The stages at which the buyer has to come to the store. */
const PICKUP_STAGES = ['payment_verified', 'reserved', 'ready_for_pickup'];

let latestPickupMessageId = null;

function pickupStageOf(msg) {
    const order = msg.order || {};
    const stage = msg.kind === 'order_update' ? (msg.order_status_at || order.status) : order.status;
    return PICKUP_STAGES.includes(stage);
}

/** Where to collect: the map on the newest pickup card, the address and buttons on every one. */
function locationHtml(withMap) {
    return `
        <div class="loc-block">
            ${withMap ? `<iframe title="Map of ${escapeAttr(STORE.name)}" src="${escapeAttr(STORE.embed)}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>` : ''}
            <div class="loc-body">
                <p class="loc-name"><i class="fas fa-location-dot" style="color: var(--brand-600);"></i>Pick up at ${escapeHtml(STORE.name)}</p>
                <p class="loc-addr">${escapeHtml(STORE.address)}</p>
                <div class="loc-actions">
                    <a class="fm-btn primary sm" href="${escapeAttr(STORE.directions)}" target="_blank" rel="noopener"><i class="fas fa-diamond-turn-right"></i>Directions</a>
                    <a class="fm-btn ghost sm" href="${escapeAttr(STORE.maps)}" target="_blank" rel="noopener"><i class="fas fa-map"></i>Open in Maps</a>
                </div>
            </div>
        </div>`;
}

function peso(amount) {
    const value = Number(amount);
    if (!isFinite(value)) return PESO + '0.00';
    return PESO + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function paymentMethodLabel(method) {
    return { gcash: 'GCash', points_full: 'Points only', cash: 'Cash at store' }[method] || (method || 'Unknown');
}

/**
 * Whether the money has actually arrived.
 *
 * Deliberately separate from the order status: an order can be reserved while
 * its payment is still unverified, and the payment is what is being asked
 * about.
 */
function paymentStateBadge(order, statusAt) {
    const status = statusAt || order.payment_status;
    const map = {
        verified: [order.is_full_points_checkout ? 'Paid with points' : 'Paid', 'success'],
        proof_submitted: ['Checking payment', 'warning'],
        rejected: ['Payment declined', 'danger'],
    };
    const unpaid = order.payment_method === 'cash'
        ? (order.status === 'pending_payment' ? ['Waiting for approval', 'warning'] : ['Pay on pickup', 'info'])
        : ['Not paid yet', 'warning'];
    const [label, tone] = map[status] || unpaid;
    return badge(label, tone);
}

function orderStatusBadge(status, paymentMethod) {
    const awaiting = paymentMethod === 'cash' ? ['Awaiting admin approval', 'warning'] : ['Awaiting payment', 'warning'];
    const map = {
        pending_payment: awaiting,
        payment_proof_submitted: ['Proof submitted', 'info'],
        payment_verified: ['Payment verified', 'info'],
        reserved: ['Reserved', 'info'],
        ready_for_pickup: ['Ready for pickup', 'brand'],
        completed: ['Completed', 'success'],
        cancelled: ['Cancelled', ''],
        rejected: ['Rejected', 'danger'],
    };
    const [label, tone] = map[status] || [status, ''];
    return badge(label, tone);
}

function badge(label, tone) {
    return `<span class="fm-badge ${tone || ''}">${escapeHtml(label)}</span>`;
}

function summaryRow(label, value, strong) {
    return `
        <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 3px 0;">
            <span style="color: var(--ink-500);">${escapeHtml(label)}</span>
            <span style="color: var(--ink-900); ${strong ? 'font-weight: 700;' : ''} text-align: right;">${escapeHtml(value)}</span>
        </div>`;
}

function cardShell(isAdmin, icon, heading, meta, body, footer) {
    return `
        <div class="card ${isAdmin ? 'me' : ''}">
            <div style="background: white; border: 1px solid var(--line); border-radius: 12px; box-shadow: var(--shadow-sm); overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; background: var(--surface-sunk); border-bottom: 1px solid var(--line);">
                    <span style="font-size: 12px; font-weight: 700; color: var(--brand-700);"><i class="fas ${icon}"></i> ${escapeHtml(heading)}</span>
                    <span style="font-size: 11px; color: var(--ink-500);">${escapeHtml(meta)}</span>
                </div>
                <div style="padding: 14px;">${body}<p style="margin: 8px 0 0 0; font-size: 11px; color: var(--ink-400);">${escapeHtml(footer)}</p></div>
            </div>
        </div>`;
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

    const body = `
        <div onclick="openItem(${order.item_id})" style="display: flex; gap: 10px; align-items: center; cursor: pointer; margin-bottom: 12px;">
            ${photo
                ? `<img src="${escapeAttr(photo)}" alt="" style="width: 56px; height: 56px; border-radius: 6px; object-fit: cover; flex-shrink: 0;">`
                : `<div class="thumb" style="width: 56px; height: 56px;"><i class="fas fa-image"></i></div>`}
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
            ${paymentStateBadge(order, msg.payment_status_at)}
            ${orderStatusBadge(msg.order_status_at || order.status, order.payment_method)}
        </div>
        ${order.payment_proof ? `
            <img src="${escapeAttr(order.payment_proof)}" alt="Payment receipt" data-proof="${escapeAttr(order.payment_proof)}" data-reference="${escapeAttr(order.payment_reference || '')}"
                 onclick="openProof(this.dataset.proof, this.dataset.reference)" style="margin-top: 10px; width: 100%; height: 150px; object-fit: cover; border-radius: 8px; cursor: pointer;">
            <p style="margin: 4px 0 0 0; font-size: 11px; color: var(--ink-500);">Click the receipt to see it in full</p>` : ''}
        ${msg.kind === 'order_update' ? `<p style="margin: 10px 0 0 0; padding: 8px 10px; background: var(--surface-sunk); border-radius: 6px; font-size: 12px; color: var(--ink-700); white-space: pre-line;">${escapeHtml(msg.message)}</p>` : ''}
        ${pickupStageOf(msg) ? locationHtml(Number(msg.message_id) === Number(latestPickupMessageId)) : ''}
        ${carriesActions(msg) ? orderActionsHtml(order) : ''}`;

    return cardShell(isAdmin, heading[0], heading[1], order.receipt_no || ('#' + order.transaction_id), body, `${senderName} · ${timestamp}`);
}

/**
 * Which card in the thread carries the buttons.
 *
 * Every order card points at the same order, so only one offers the decisions:
 * the receipt if the buyer sent one, otherwise the order itself.
 */
function carriesActions(msg) {
    return msg.kind === 'payment_submitted' || (msg.kind === 'order_placed' && !msg.order.payment_proof);
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

    if (actions.includes('verify_payment')) buttons.push(actionButton(id, 'verify-payment', 'Approve', 'primary'));
    if (actions.includes('approve_order')) buttons.push(actionButton(id, 'approve-order', 'Approve', 'primary'));
    if (actions.includes('complete')) buttons.push(actionButton(id, 'complete', 'Complete', 'primary'));
    if (actions.includes('mark_ready_for_pickup')) buttons.push(actionButton(id, 'ready-for-pickup', 'Ready for pickup', 'ghost'));
    if (actions.includes('reject_payment')) buttons.push(actionButton(id, 'reject-payment', 'Decline', 'danger'));
    else if (actions.includes('cancel')) buttons.push(actionButton(id, 'cancel', 'Cancel order', 'danger'));

    return `<div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; border-top: 1px solid var(--surface-sunk); padding-top: 12px;">${buttons.join('')}</div>`;
}

function actionButton(id, endpoint, label, tone) {
    // Endpoint and label are both fixed strings chosen just above, so single
    // quotes inside the attribute are safe here.
    return `<button class="fm-btn ${tone} sm" onclick="runOrderAction(${id}, '${endpoint}', '${label}')">${escapeHtml(label)}</button>`;
}

async function runOrderAction(transactionId, endpoint, label) {
    if (busyAction) return;

    const needsReason = endpoint === 'reject-payment' || endpoint === 'cancel';
    let reason = null;

    if (needsReason) {
        reason = await askModal({
            title: label,
            body: 'The buyer is told in this chat. Give them the reason.',
            field: { label: 'Reason', type: 'textarea', placeholder: 'Shown to the buyer' },
            confirmLabel: label,
            danger: true,
        });
        if (reason === null) return;
        if (!reason.trim()) { showToast('A reason is required.', 'error'); return; }
    } else {
        const confirmed = await askModal({
            title: `${label} this order?`,
            body: endpoint === 'approve-order'
                ? 'The item is held and the buyer gets their pickup code. It is not marked paid - the cash is taken when they collect it.'
                : 'The buyer sees the result in this conversation right away.',
            confirmLabel: label,
        });
        if (confirmed === null) return;
    }

    busyAction = true;
    try {
        const response = await fetch(`${API}/admin/transactions/${transactionId}/${endpoint}`, {
            method: 'POST', headers: authHeaders(true), body: JSON.stringify(needsReason ? { reason: reason.trim() } : {}),
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
        await loadThread();
    } catch (error) {
        showToast(`Could not ${label.toLowerCase()}: ${error.message}`, 'error');
    } finally {
        busyAction = false;
    }
}

// ── The decision modal ───────────────────────────────────────────────────

let actionModalResolve = null;

/**
 * One modal for every decision. Resolves with the field's value on confirm
 * (an empty string when there is no field), or null on cancel.
 */
function askModal({ title, body, field, confirmLabel, danger }) {
    return new Promise((resolve) => {
        actionModalResolve = resolve;

        const fieldHtml = !field ? '' : field.type === 'textarea'
            ? `<textarea id="actionModalInput" rows="3" class="fm-input" style="margin-top: 10px;" placeholder="${escapeAttr(field.placeholder || '')}">${escapeHtml(field.value || '')}</textarea>`
            : `<input id="actionModalInput" type="${field.type || 'text'}" class="fm-input" style="margin-top: 10px;" value="${escapeAttr(field.value || '')}" placeholder="${escapeAttr(field.placeholder || '')}">`;

        document.getElementById('actionModalBody').innerHTML = `
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--ink-900);">${escapeHtml(title)}</h4>
            <p style="margin: 8px 0 0 0; font-size: 13px; color: var(--ink-600);">${escapeHtml(body || '')}</p>
            ${field && field.label ? `<label class="fm-label" style="margin-top: 12px;">${escapeHtml(field.label)}</label>` : ''}
            ${fieldHtml}
            <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px;">
                <button class="fm-btn ghost" onclick="actionModalDone(false)">Cancel</button>
                <button class="fm-btn ${danger ? 'danger' : 'primary'}" onclick="actionModalDone(true)">${escapeHtml(confirmLabel || 'Confirm')}</button>
            </div>`;

        document.getElementById('actionModal').style.display = 'flex';
        setTimeout(() => {
            const input = document.getElementById('actionModalInput');
            input?.focus();
            input?.addEventListener('keydown', (e) => { if (e.key === 'Enter' && input.tagName !== 'TEXTAREA') actionModalDone(true); });
        }, 60);
    });
}

function actionModalDone(confirmed) {
    const input = document.getElementById('actionModalInput');
    const value = input ? input.value : '';
    document.getElementById('actionModal').style.display = 'none';
    const resolve = actionModalResolve;
    actionModalResolve = null;
    if (resolve) resolve(confirmed ? value : null);
}

// ── Listing offers ───────────────────────────────────────────────────────

function renderItemOfferCard(msg, isAdmin, senderName, timestamp) {
    const item = msg.item_card;
    const photo = (item.photos && item.photos[0]) || null;
    const pending = (item.status || '').toLowerCase() === 'pending';
    // A price on a pending listing IS the acceptance; from there the next
    // move is scheduling the turnover, not deciding again.
    const accepted = pending && item.acquisition_price;

    const body = `
        <div onclick="openItem(${item.item_id})" style="display: flex; gap: 10px; align-items: center; cursor: pointer; margin-bottom: 12px;">
            ${photo
                ? `<img src="${escapeAttr(photo)}" alt="" style="width: 56px; height: 56px; border-radius: 6px; object-fit: cover; flex-shrink: 0;">`
                : `<div class="thumb" style="width: 56px; height: 56px;"><i class="fas fa-image"></i></div>`}
            <div style="min-width: 0;">
                <p style="margin: 0; font-size: 13px; font-weight: 600; color: var(--ink-900);">${escapeHtml(item.title || ('Item #' + item.item_id))}</p>
                <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--ink-500);">Asking ${peso(item.seller_asking_price)}</p>
                <p style="margin: 2px 0 0 0; font-size: 11px; color: var(--brand-600); font-weight: 600;">Click to view item</p>
            </div>
        </div>
        <div style="border-top: 1px solid var(--surface-sunk); padding-top: 8px;">
            ${summaryRow('Asking price', peso(item.seller_asking_price), true)}
            ${item.acquisition_price ? summaryRow('Store offer', peso(item.acquisition_price)) : ''}
            ${'' /* BOOKING/SCHEDULE DISABLED - no longer required
            ${item.meetup_schedule ? summaryRow('Meet-up', new Date(item.meetup_schedule).toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' })) : ''}
            */}
        </div>
        <div style="margin-top: 10px;">${itemBadge(item.status || 'pending')}</div>
        ${item.rejected_reason ? `<p style="margin: 10px 0 0 0; padding: 8px 10px; background: var(--danger-bg); border-radius: 6px; font-size: 12px; color: var(--danger);">${escapeHtml(item.rejected_reason)}</p>` : ''}
        ${accepted ? `
            <div style="display: flex; gap: 8px; margin-top: 12px; border-top: 1px solid var(--surface-sunk); padding-top: 12px;">
                ${'' /* BOOKING/SCHEDULE DISABLED - no longer required
                <button class="fm-btn ghost sm" onclick="scheduleMeetup(${item.item_id})">${item.meetup_schedule ? 'Change schedule' : 'Set schedule'}</button>
                */}
                <button class="fm-btn primary sm" onclick="acquireItem(${item.item_id})">Mark acquired</button>
            </div>` : pending ? `
            <div style="display: flex; gap: 8px; margin-top: 12px; border-top: 1px solid var(--surface-sunk); padding-top: 12px;">
                <button class="fm-btn primary sm" onclick="acceptOffer(${item.item_id}, '${escapeAttr(item.seller_asking_price || '')}')">Accept &middot; set price</button>
                <button class="fm-btn danger sm" onclick="rejectOffer(${item.item_id})">Reject</button>
            </div>` : ''}`;

    return cardShell(isAdmin, 'fa-tag', 'Item offer', 'Item #' + item.item_id, body, `${senderName} · ${timestamp}`);
}

/**
 * Buttons only: Accept/Reject while negotiating, schedule and acquire once
 * accepted. The card in the thread tells the whole story; this strip exists
 * for when the story has scrolled away.
 */
function renderPinnedOffer(item) {
    const bar = document.getElementById('offerPinned');
    const pending = item && (item.status || '').toLowerCase() === 'pending';

    if (!pending) { bar.style.display = 'none'; bar.innerHTML = ''; return; }

    bar.innerHTML = item.acquisition_price
        ? /* BOOKING/SCHEDULE DISABLED - no longer required:
           `<button class="fm-btn ghost sm" onclick="scheduleMeetup(${item.item_id})">${item.meetup_schedule ? 'Change schedule' : 'Set schedule'}</button>` */
          `<button class="fm-btn primary sm" onclick="acquireItem(${item.item_id})">Mark acquired</button>`
        : `<button class="fm-btn primary sm" onclick="acceptOffer(${item.item_id}, '${escapeAttr(item.seller_asking_price || '')}')">Accept offer</button>
           <button class="fm-btn danger sm" onclick="rejectOffer(${item.item_id})">Reject</button>`;
    bar.style.display = 'flex';
}

async function itemPost(itemId, path, body, failLabel) {
    if (busyAction) return;
    busyAction = true;
    try {
        const response = await fetch(`${API}/admin/items/${itemId}/${path}`, { method: 'POST', headers: authHeaders(true), body: JSON.stringify(body) });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
        return payload;
    } catch (error) {
        showToast(`${failLabel}: ${error.message}`, 'error');
        return null;
    } finally {
        busyAction = false;
    }
}

/** Accepting an offer is setting the acquisition price - the same first step the inventory workflow takes. */
async function acceptOffer(itemId, askingPrice) {
    const price = await askModal({
        title: 'Accept this offer',
        body: 'Set the acquisition price - what the store pays the seller. Receiving the item at the counter comes after this.',
        field: { label: 'Acquisition price (₱)', type: 'number', value: askingPrice, placeholder: 'e.g. 300.00' },
        confirmLabel: 'Accept offer',
    });
    if (price === null) return;
    if (!price.trim() || isNaN(Number(price))) { showToast('Enter a valid peso amount, e.g. 300 or 299.50.', 'error'); return; }
    if (await itemPost(itemId, 'acquisition-price', { acquisition_price: price.trim() }, 'Could not accept the offer')) await loadThread();
}

// BOOKING/SCHEDULE DISABLED - no longer required
// /**
//  * When the seller comes in: a day this month, a slot in store hours - the
//  * same picker and the same rules as the app. The 6h/1h/30m reminders count
//  * down from it.
//  */
// async function scheduleMeetup(itemId) {
//     const card = [...currentMessages].reverse().find(m => m.kind === 'item_listed' && m.item_card && Number(m.item_card.item_id) === Number(itemId));
//     const when = await FMMeetup.pick({ current: card?.item_card?.meetup_schedule || null });
//     if (when === null) return;
//     if (await itemPost(itemId, 'meetup', { meetup_schedule: when }, 'Could not save the schedule')) await loadThread();
// }

/**
 * Receiving the item, at the counter.
 *
 * This used to be a yes/no box that quietly called verify-turnover with
 * nothing attached, so a turnover recorded from the chat had neither of the
 * two photographs the counter exists to take. It now opens the turnover panel
 * instead: carry on with the phone by scanning its QR, or do it here with the
 * proof picked from this computer. Either way the seller's payout, the
 * selling price and the status are part of the same step.
 */
function acquireItem(itemId) {
    openTurnover(itemId, () => loadThread());
}

async function rejectOffer(itemId) {
    const reason = await askModal({
        title: 'Reject this offer',
        body: 'The seller is told in this chat why their offer was turned down.',
        field: { label: 'Reason', type: 'textarea', placeholder: 'Shown to the seller' },
        confirmLabel: 'Reject offer',
        danger: true,
    });
    if (reason === null) return;
    if (!reason.trim()) { showToast('A reason is required.', 'error'); return; }
    if (await itemPost(itemId, 'reject', { reason: reason.trim() }, 'Could not reject the offer')) await loadThread();
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
            <h4 style="margin: 0; font-size: 16px; font-weight: 700;">Payment receipt</h4>
            <button onclick="closeOverlay()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: var(--ink-500);">&times;</button>
        </div>
        ${reference ? `<p style="margin: 0 0 12px 0; font-size: 13px; color: var(--ink-700);">Reference: <strong>${escapeHtml(reference)}</strong></p>` : ''}
        <img src="${escapeAttr(url)}" alt="Payment receipt" style="width: 100%; border-radius: 8px;">`);
}

/** The listing behind an order, opened from the card's photo. */
async function openItem(itemId) {
    showOverlay('<p style="font-size: 13px; color: var(--ink-500);">Loading item...</p>');

    try {
        const response = await fetch(`${API}/items/${itemId}`, { headers: authHeaders() });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

        const item = payload.data || {};
        const photos = item.photos || [];

        showOverlay(`
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                <h4 style="margin: 0; font-size: 18px; font-weight: 700;">${escapeHtml(item.title || 'Item')}</h4>
                <button onclick="closeOverlay()" style="background: none; border: none; font-size: 18px; cursor: pointer; color: var(--ink-500);">&times;</button>
            </div>
            ${photos.length ? `<div style="display: flex; gap: 8px; overflow-x: auto; margin-bottom: 12px;">${photos.map(url => `<img src="${escapeAttr(url)}" alt="" style="height: 180px; border-radius: 8px; object-fit: cover;">`).join('')}</div>` : ''}
            <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 12px;">
                <span style="font-size: 20px; font-weight: 700; color: var(--brand-700);">${peso(item.public_price || item.seller_asking_price || 0)}</span>
                ${itemBadge(item.status || 'unknown')}
                ${item.reward_points ? badge(`Earn ${item.reward_points} point(s)`, 'reward') : ''}
            </div>
            ${item.description ? `<p style="font-size: 13px; color: var(--ink-700); white-space: pre-line;">${escapeHtml(item.description)}</p>` : ''}
            <div style="border-top: 1px solid var(--surface-sunk); margin-top: 12px; padding-top: 10px;">
                ${item.acquisition_price ? summaryRow('Acquisition price', peso(item.acquisition_price)) : ''}
                ${item.markup ? summaryRow('Markup', peso(item.markup)) : ''}
                ${item.seller_email ? summaryRow('Seller', item.seller_email) : ''}
            </div>`);
    } catch (error) {
        showOverlay(`<p style="font-size: 13px; color: var(--danger);">Could not load the item: ${escapeHtml(error.message)}</p>
            <button class="fm-btn primary" style="margin-top: 12px;" onclick="closeOverlay()">Close</button>`);
    }
}

document.getElementById('searchInput').addEventListener('input', renderConversations);

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

/** The same, for a value written into a double-quoted HTML attribute. */
function escapeAttr(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
        .replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
</script>
@endpush
