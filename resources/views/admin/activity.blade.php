@extends('layouts.admin-dashboard')

@section('title', 'Activity logs')
@section('subtitle', 'Everything that has happened in the store')

@section('content')
{{--
    The store's history, with the people in it.

    This page used to draw the same green-to-blue gradient circle beside every
    line, so a student registering looked exactly like the store handing an
    item over, and there was nothing to click: the sentence was all there was.
    Each row now carries the face of whoever it belongs to - the student's own
    photo on their registration, the admin's on a handover - and opens on the
    facts behind the sentence.

    The filters above act on the rows themselves, which is what they always
    looked like they did.
--}}
<div class="space-y-6">
    <div class="fm-card fm-card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="fm-label" for="filterPerson">Person</label>
                <input id="filterPerson" type="search" placeholder="Search a name or email…" class="fm-input" autocomplete="off">
            </div>
            <div>
                <label class="fm-label" for="filterAction">Action</label>
                <select id="filterAction" class="fm-select">
                    <option value="">All actions</option>
                    <option value="create">Created</option>
                    <option value="update">Updated</option>
                    <option value="purchase">Purchases</option>
                    <option value="delete">Cancelled</option>
                </select>
            </div>
            <div>
                <label class="fm-label" for="filterType">Kind</label>
                <select id="filterType" class="fm-select">
                    <option value="">Everything</option>
                    <option value="user">Students</option>
                    <option value="item">Items</option>
                    <option value="order">Orders</option>
                    <option value="points">Points</option>
                </select>
            </div>
            <div>
                <label class="fm-label" for="filterDate">Date</label>
                <input id="filterDate" type="date" class="fm-input">
            </div>
        </div>
        <p id="activityCount" class="cell-sub" style="margin-top: 12px;"></p>
    </div>

    <div class="fm-card">
        <div class="fm-divided" id="activityList"></div>
    </div>
</div>

{{-- One row, opened. --}}
<div id="activityModal"
     onclick="if (event.target === this) closeActivity()"
     style="display: none; position: fixed; inset: 0; background: rgba(17,24,39,0.82); z-index: 80; align-items: center; justify-content: center; padding: 24px;">
    <div style="background: white; border-radius: 14px; max-width: 460px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 18px; border-bottom: 1px solid var(--line);">
            <h4 style="margin: 0; font-size: 16px; font-weight: 700;">Activity</h4>
            <button onclick="closeActivity()" style="background: none; border: none; font-size: 22px; line-height: 1; cursor: pointer; color: var(--ink-500);">&times;</button>
        </div>
        <div id="activityModalBody" style="padding: 18px;"></div>
    </div>
</div>

@push('scripts')
<script>
    // The feed, as the API handed it over. Slashes stay unescaped so the
    // photo URLs read as URLs; the tag and quote escaping that keeps this
    // safe inside a <script> is kept.
    const ACTIVITIES = @json(
        array_values($activities ?? []),
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    const ACTION_LABEL = {
        create: 'Created',
        update: 'Updated',
        purchase: 'Purchase',
        delete: 'Cancelled',
    };

    const ACTION_TONE = {
        create: ['var(--info-bg)', 'var(--info)'],
        update: ['var(--brand-100)', 'var(--brand-700)'],
        purchase: ['var(--success-bg)', 'var(--success)'],
        delete: ['var(--danger-bg)', 'var(--danger)'],
    };

    const ACTION_ICON = {
        create: 'fa-plus',
        update: 'fa-pen',
        purchase: 'fa-cart-shopping',
        delete: 'fa-xmark',
    };

    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function escAttr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function initials(name) {
        return String(name || '?').trim().split(/\s+/).slice(0, 2).map(part => part[0] || '').join('').toUpperCase() || '?';
    }

    function when(stamp) {
        const date = new Date(String(stamp || '').replace(' ', 'T'));
        return isNaN(date.getTime()) ? String(stamp || '') : date;
    }

    function shortWhen(stamp) {
        const date = when(stamp);
        if (typeof date === 'string') return date;
        const today = new Date();
        const sameDay = date.toDateString() === today.toDateString();
        return sameDay
            ? 'Today · ' + date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })
            : date.toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    /**
     * The face beside a line: the person's own photo when the API has one,
     * their initials when it does not, and never a generic icon - which is
     * what made every row look the same.
     */
    function avatarHtml(name, photo, size) {
        const px = size || 44;

        if (photo) {
            return `<img src="${escAttr(photo)}" alt=""
                        style="width: ${px}px; height: ${px}px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background: var(--surface-sunk);">`;
        }

        return `<div style="width: ${px}px; height: ${px}px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
                    background: var(--brand-100); color: var(--brand-700); font-weight: 700; font-size: ${Math.round(px * 0.36)}px;">${esc(initials(name))}</div>`;
    }

    function matchesFilters(entry) {
        const person = document.getElementById('filterPerson').value.trim().toLowerCase();
        const action = document.getElementById('filterAction').value;
        const type = document.getElementById('filterType').value;
        const date = document.getElementById('filterDate').value;

        if (action && entry.action !== action) return false;
        if (type && entry.resource_type !== type) return false;

        if (person) {
            const haystack = [entry.user, entry.user_email, entry.description, entry.subject?.name].join(' ').toLowerCase();
            if (!haystack.includes(person)) return false;
        }

        if (date) {
            const stamp = String(entry.timestamp || '');
            if (!stamp.startsWith(date)) return false;
        }

        return true;
    }

    function render() {
        const host = document.getElementById('activityList');
        const rows = ACTIVITIES.filter(matchesFilters);

        document.getElementById('activityCount').textContent = rows.length === ACTIVITIES.length
            ? `${ACTIVITIES.length} event${ACTIVITIES.length === 1 ? '' : 's'}`
            : `${rows.length} of ${ACTIVITIES.length} events`;

        if (!rows.length) {
            host.innerHTML = `
                <div class="fm-empty" style="padding: 48px 16px;">
                    <i class="fas fa-history"></i>
                    <p>Nothing to show</p>
                    <span>${ACTIVITIES.length ? 'No activity matches these filters.' : 'The store has no recorded activity yet.'}</span>
                </div>`;
            return;
        }

        host.innerHTML = rows.map((entry) => {
            const index = ACTIVITIES.indexOf(entry);
            const [background, color] = ACTION_TONE[entry.action] || ['var(--surface-sunk)', 'var(--ink-600)'];

            return `
                <button type="button" onclick="openActivity(${index})"
                        style="display: flex; gap: 14px; align-items: center; width: 100%; text-align: left; padding: 14px 20px; background: none; border: none; cursor: pointer;"
                        onmouseover="this.style.background='var(--surface-sunk)'" onmouseout="this.style.background='none'">
                    <div style="position: relative; flex-shrink: 0;">
                        ${avatarHtml(entry.user, entry.user_photo, 44)}
                        <span style="position: absolute; right: -2px; bottom: -2px; width: 18px; height: 18px; border-radius: 50%; border: 2px solid white;
                                     display: flex; align-items: center; justify-content: center; background: ${background}; color: ${color}; font-size: 8px;">
                            <i class="fas ${ACTION_ICON[entry.action] || 'fa-circle'}"></i>
                        </span>
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <p class="cell-title" style="white-space: normal;">${esc(entry.description)}</p>
                        <p class="cell-sub truncate">${esc(entry.user)} · ${esc(shortWhen(entry.timestamp))}</p>
                    </div>

                    ${entry.subject && entry.subject.name && entry.subject.user_id !== entry.user_id
                        ? `<div title="${escAttr(entry.subject.name)}">${avatarHtml(entry.subject.name, entry.subject.photo, 26)}</div>`
                        : ''}

                    <span class="fm-badge">${esc(entry.resource_type || '')}</span>
                    <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                </button>`;
        }).join('');
    }

    function openActivity(index) {
        const entry = ACTIVITIES[index];
        if (!entry) return;

        const date = when(entry.timestamp);
        const full = typeof date === 'string' ? date : date.toLocaleString([], {
            weekday: 'long', month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit',
        });

        const row = (label, value) => `
            <div style="display: flex; justify-content: space-between; gap: 14px; padding: 7px 0; font-size: 13px; border-bottom: 1px solid var(--surface-sunk);">
                <span style="color: var(--ink-500);">${esc(label)}</span>
                <span style="font-weight: 600; text-align: right;">${esc(value)}</span>
            </div>`;

        document.getElementById('activityModalBody').innerHTML = `
            <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 14px;">
                ${avatarHtml(entry.user, entry.user_photo, 52)}
                <div style="min-width: 0;">
                    <p style="margin: 0; font-size: 15px; font-weight: 700;">${esc(entry.user)}</p>
                    <p style="margin: 2px 0 0; font-size: 12.5px; color: var(--ink-500);">
                        ${esc(entry.user_email || '')}${entry.user_role ? ' · ' + esc(entry.user_role) : ''}</p>
                </div>
            </div>

            <p style="margin: 0 0 14px; font-size: 14px; color: var(--ink-900);">${esc(entry.description)}</p>

            ${entry.subject && entry.subject.name && entry.subject.user_id !== entry.user_id ? `
                <div style="display: flex; gap: 10px; align-items: center; padding: 10px; border-radius: 10px; background: var(--surface-sunk); margin-bottom: 14px;">
                    ${avatarHtml(entry.subject.name, entry.subject.photo, 34)}
                    <div style="min-width: 0;">
                        <p style="margin: 0; font-size: 12px; color: var(--ink-500);">The other person</p>
                        <p style="margin: 1px 0 0; font-size: 13.5px; font-weight: 600;">${esc(entry.subject.name)}</p>
                    </div>
                </div>` : ''}

            ${row('Action', ACTION_LABEL[entry.action] || entry.action)}
            ${row('Kind', entry.resource_type)}
            ${row('Reference', '#' + entry.resource_id)}
            ${row('When', full)}
            ${(entry.details || []).map(detail => row(detail.label, detail.value)).join('')}`;

        document.getElementById('activityModal').style.display = 'flex';
    }

    function closeActivity() {
        document.getElementById('activityModal').style.display = 'none';
    }

    ['filterPerson', 'filterAction', 'filterType', 'filterDate'].forEach((id) => {
        document.getElementById(id).addEventListener('input', render);
        document.getElementById(id).addEventListener('change', render);
    });

    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeActivity(); });

    render();
</script>
@endpush
@endsection
