@extends('layouts.admin-dashboard')

@section('title', 'Dashboard')
@section('subtitle', 'How the store is doing today')

@section('actions')
    <a href="{{ route('admin.counter') }}" class="fm-btn primary">
        <i class="fas fa-qrcode"></i>Counter
    </a>
@endsection

@section('content')
<div class="space-y-6">
    <!-- USERS SECTION -->
    <div>
        <h3 class="fm-section-title">Students</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('admin.students') }}" class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="stat-label">Total</p>
                        <div class="stat-value">{{ $stats['users']['total_students'] ?? 0 }}</div>
                    </div>
                    <div class="stat-icon" style="background: var(--info-bg); color: var(--info);">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.students') }}" class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="stat-label">Active</p>
                        <div class="stat-value">{{ $stats['users']['active_students'] ?? 0 }}</div>
                    </div>
                    <div class="stat-icon" style="background: var(--success-bg); color: var(--success);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.students') }}" class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="stat-label">Pending approval</p>
                        <div class="stat-value">{{ $stats['users']['pending_students'] ?? 0 }}</div>
                    </div>
                    <div class="stat-icon" style="background: var(--warning-bg); color: var(--warning);">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </a>

            <a href="{{ route('admin.students') }}" class="stat-card">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="stat-label">Verified</p>
                        <div class="stat-value">{{ $stats['users']['verified_students'] ?? 0 }}</div>
                    </div>
                    <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- ITEMS SECTION -->
    <div>
        <h3 class="fm-section-title">Inventory</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <a href="{{ route('admin.private-offers') }}" class="stat-card">
                <p class="stat-label">Total</p>
                <div class="stat-value">{{ $stats['items']['total_items'] ?? 0 }}</div>
            </a>
            <a href="{{ route('admin.private-offers') }}" class="stat-card">
                <p class="stat-label">Offers to review</p>
                <div class="stat-value">{{ $stats['items']['private_items'] ?? 0 }}</div>
            </a>
            <a href="{{ route('admin.acquired-items') }}" class="stat-card">
                <p class="stat-label">Acquired</p>
                <div class="stat-value">{{ $stats['items']['acquired_items'] ?? 0 }}</div>
            </a>
            <a href="{{ route('admin.public-listings') }}" class="stat-card">
                <p class="stat-label">Published</p>
                <div class="stat-value">{{ $stats['items']['public_items'] ?? 0 }}</div>
            </a>
            <a href="{{ route('admin.reserved-items') }}" class="stat-card">
                <p class="stat-label">Reserved</p>
                <div class="stat-value">{{ $stats['items']['reserved_items'] ?? 0 }}</div>
            </a>
            <a href="{{ route('admin.sold-items') }}" class="stat-card">
                <p class="stat-label">Sold</p>
                <div class="stat-value">{{ $stats['items']['sold_items'] ?? 0 }}</div>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <div class="lg:col-span-2 space-y-6">
            {{--
                The meet-ups Ofelia has booked - sellers due at the store with an
                item - read from the pending offers, the same way the mobile home
                screen shows them. Receiving the item clears it from the list.
            --}}
            <section class="fm-card">
                <div class="fm-card-head">
                    <div>
                        <h4>Meet-ups</h4>
                        <p class="cell-sub" style="margin-top: 2px;">Sellers bringing their items to the store</p>
                    </div>
                    <a href="{{ route('admin.conversations') }}" class="fm-btn ghost sm">Open chat</a>
                </div>
                <div id="meetupsList" class="fm-divided">
                    <div style="padding: 20px; text-align: center;"><span class="loading-spinner"></span></div>
                </div>
            </section>

            @php
                $recentActivities = $stats['recent_activities'] ?? [];
                $registrations = $recentActivities['recent_registrations'] ?? [];
                $items = $recentActivities['recent_items'] ?? [];
                $verifications = $recentActivities['pending_verifications'] ?? [];
            @endphp

            @if (!empty($verifications))
            <section class="fm-card">
                <div class="fm-card-head">
                    <h4>Pending verifications</h4>
                    <a href="{{ route('admin.students') }}" class="fm-btn primary sm">Review</a>
                </div>
                <div class="fm-divided">
                    @foreach($verifications as $verification)
                        <div class="flex items-center gap-4 px-5 py-3">
                            <div class="stat-icon" style="background: var(--warning-bg); color: var(--warning);"><i class="fas fa-certificate"></i></div>
                            <div class="flex-1 min-w-0">
                                <p class="cell-title">{{ $verification['student_name'] ?? 'N/A' }}</p>
                                <p class="cell-sub truncate">{{ $verification['email'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            @if (!empty($items))
            <section class="fm-card">
                <div class="fm-card-head">
                    <h4>Recent items</h4>
                    <a href="{{ route('admin.private-offers') }}" class="fm-btn ghost sm">View all</a>
                </div>
                <div class="fm-divided">
                    @foreach($items as $item)
                        <div class="flex items-center gap-4 px-5 py-3">
                            <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-shopping-bag"></i></div>
                            <div class="flex-1 min-w-0">
                                <p class="cell-title truncate">{{ $item['title'] ?? 'N/A' }}</p>
                                <p class="cell-sub truncate">{{ $item['seller'] ?? 'N/A' }}</p>
                            </div>
                            <span class="fm-badge">{{ ucfirst($item['status'] ?? 'pending') }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif

            @if (!empty($registrations))
            <section class="fm-card">
                <div class="fm-card-head">
                    <h4>Recent registrations</h4>
                    <a href="{{ route('admin.students') }}" class="fm-btn ghost sm">View all</a>
                </div>
                <div class="fm-divided">
                    @foreach($registrations as $registration)
                        <div class="flex items-center gap-4 px-5 py-3">
                            <div class="stat-icon" style="background: var(--info-bg); color: var(--info);"><i class="fas fa-user"></i></div>
                            <div class="flex-1 min-w-0">
                                <p class="cell-title">{{ $registration['name'] ?? 'N/A' }}</p>
                                <p class="cell-sub truncate">{{ $registration['email'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif
        </div>

        <div class="space-y-6">
            <section class="fm-card">
                <div class="fm-card-head"><h4>Quick actions</h4></div>
                <div class="fm-divided">
                    <a href="{{ route('admin.counter') }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <span class="flex items-center gap-3"><i class="fas fa-qrcode" style="color: var(--brand-600); width: 16px;"></i>Scan at the counter</span>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.private-offers') }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <span class="flex items-center gap-3"><i class="fas fa-inbox" style="color: var(--brand-600); width: 16px;"></i>Review offers</span>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.transactions.history') }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <span class="flex items-center gap-3"><i class="fas fa-receipt" style="color: var(--brand-600); width: 16px;"></i>Orders to approve</span>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.students') }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <span class="flex items-center gap-3"><i class="fas fa-users" style="color: var(--brand-600); width: 16px;"></i>Approve students</span>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.conversations') }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <span class="flex items-center gap-3"><i class="fas fa-comments" style="color: var(--brand-600); width: 16px;"></i>Chat</span>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                    <a href="{{ route('admin.settings') }}" class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 transition">
                        <span class="flex items-center gap-3"><i class="fas fa-clock" style="color: var(--brand-600); width: 16px;"></i>Store hours &amp; GCash</span>
                        <i class="fas fa-chevron-right" style="color: var(--ink-400);"></i>
                    </a>
                </div>
            </section>

            <section class="fm-card">
                <div class="fm-card-head"><h4>Store today</h4></div>
                <div class="fm-card-body" id="storeToday" style="font-size: 13.5px;">
                    <span class="loading-spinner"></span>
                </div>
            </section>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const API = 'https://fati-api.alertaraqc.com/api';
        const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
        const headers = { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' };

        function esc(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        async function loadMeetups() {
            const host = document.getElementById('meetupsList');
            try {
                const response = await fetch(`${API}/admin/items?status=pending`, { headers });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

                const now = Date.now();
                const meetups = (payload.data || [])
                    .map(item => ({ item, at: item.meetup_schedule ? new Date(item.meetup_schedule) : null }))
                    .filter(m => m.at && !isNaN(m.at.getTime()))
                    .sort((a, b) => a.at - b.at);

                if (!meetups.length) {
                    host.innerHTML = '<p style="padding: 18px 20px; font-size: 13px; color: var(--ink-500); margin: 0;">No meet-ups booked. Set one from an accepted offer in its chat.</p>';
                    return;
                }

                const today = new Date(); today.setHours(0, 0, 0, 0);
                const tomorrow = new Date(today); tomorrow.setDate(today.getDate() + 1);

                host.innerHTML = meetups.slice(0, 8).map(({ item, at }) => {
                    const missed = at.getTime() < now;
                    const day = new Date(at); day.setHours(0, 0, 0, 0);
                    const dayLabel = day.getTime() === today.getTime() ? 'Today'
                        : day.getTime() === tomorrow.getTime() ? 'Tomorrow'
                        : at.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
                    const clock = at.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

                    return `
                        <div class="flex items-center gap-4 px-5 py-3">
                            <div style="width: 46px; height: 46px; border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: ${missed ? 'var(--danger-bg)' : 'var(--brand-100)'}; color: ${missed ? 'var(--danger)' : 'var(--brand-800)'}; flex-shrink: 0;">
                                <span style="font-size: 10px; font-weight: 700; text-transform: uppercase;">${esc(at.toLocaleDateString([], { weekday: 'short' }))}</span>
                                <span style="font-size: 17px; font-weight: 700; line-height: 1;">${at.getDate()}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="cell-title truncate">${esc(item.title || ('Item #' + item.item_id))}</p>
                                <p style="font-size: 12.5px; margin: 0; color: ${missed ? 'var(--danger)' : 'var(--brand-700)'};">${esc(dayLabel)} &middot; ${esc(clock)}</p>
                                <p class="cell-sub truncate">${esc(item.seller_email || '')}</p>
                            </div>
                            ${missed ? '<span class="fm-badge danger">Missed</span>' : ''}
                        </div>`;
                }).join('');
            } catch (error) {
                host.innerHTML = `<p style="padding: 18px 20px; font-size: 13px; color: var(--danger); margin: 0;">Could not load meet-ups: ${esc(error.message)}</p>`;
            }
        }

        async function loadStoreToday() {
            const host = document.getElementById('storeToday');
            try {
                const response = await fetch(`${API}/store/hours`, { headers });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

                const hours = payload.data || {};
                const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                const openDays = Array.isArray(hours.open_days) ? hours.open_days.map(Number) : [];
                const jsDay = new Date().getDay();
                const isoDay = jsDay === 0 ? 7 : jsDay;
                const openToday = openDays.includes(isoDay);

                host.innerHTML = `
                    <div class="flex items-center justify-between gap-3" style="margin-bottom: 10px;">
                        <span style="color: var(--ink-500);">Today</span>
                        <span class="fm-badge ${openToday ? 'success' : ''}">${openToday ? 'Open' : 'Closed'}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3" style="margin-bottom: 10px;">
                        <span style="color: var(--ink-500);">Hours</span>
                        <span class="font-medium">${esc(hours.hours_label || `${String(hours.open_time || '').slice(0, 5)} - ${String(hours.close_time || '').slice(0, 5)}`)}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3" style="margin-bottom: 10px;">
                        <span style="color: var(--ink-500);">Open days</span>
                        <span class="font-medium">${esc(openDays.length === 7 ? 'Every day' : openDays.map(d => days[d - 1]).filter(Boolean).join(', '))}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span style="color: var(--ink-500);">Booking slot</span>
                        <span class="font-medium">${esc(String(hours.slot_minutes || 30))} minutes</span>
                    </div>
                    <a href="{{ route('admin.settings') }}#store-hours" class="fm-btn ghost sm" style="margin-top: 14px; width: 100%;">Change store hours</a>`;
            } catch (error) {
                host.innerHTML = `<p style="color: var(--danger); margin: 0;">Could not load store hours: ${esc(error.message)}</p>`;
            }
        }

        loadMeetups();
        loadStoreToday();
    })();
</script>
@endpush
@endsection
