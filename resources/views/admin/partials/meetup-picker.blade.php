{{--
    The meet-up picker, the same one the mobile app has: a day in the current
    month, then a slot in store hours. Only this month is shown; a day is
    greyed out when it has passed, the store is closed, or no slot is left in
    it. The hours come from the server - the figures it checks the booking
    against - so every choice offered here is one it accepts.

    Include once per page, then: const when = await FMMeetup.pick({ current });
    Resolves to "YYYY-MM-DD HH:MM:00" in the store's own zone, or null.
--}}

<div id="meetupModal" onclick="if (event.target === this) FMMeetup.cancel()"
     style="display: none; position: fixed; inset: 0; background: rgba(12, 48, 33, 0.55); z-index: 85; align-items: center; justify-content: center; padding: 24px;">
    <div style="background: var(--surface); border-radius: 16px; max-width: 420px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg);">
        <div style="padding: 18px 20px 0;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 700;">Schedule meet-up</h4>
            <p id="meetupHours" class="cell-sub" style="margin-top: 4px;"></p>
        </div>
        <div id="meetupBody" style="padding: 14px 20px 4px;"></div>
        <div style="display: flex; gap: 8px; padding: 12px 20px 18px;">
            <button class="fm-btn ghost" style="flex: 1;" onclick="FMMeetup.cancel()">Cancel</button>
            <button class="fm-btn primary" style="flex: 1;" id="meetupSave" onclick="FMMeetup.save()" disabled>Save</button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .mu-month { font-size: 14px; font-weight: 650; margin: 0 0 6px; }
    .mu-week, .mu-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
    .mu-week span { text-align: center; font-size: 11px; color: var(--ink-500); padding: 2px 0; }
    .mu-day { aspect-ratio: 1; border: none; background: none; border-radius: 50%; font-size: 13.5px; color: var(--ink-900); cursor: pointer; font-family: inherit; }
    .mu-day:hover:not(:disabled) { background: var(--brand-50); }
    .mu-day:disabled { color: var(--ink-400); opacity: 0.45; cursor: not-allowed; }
    .mu-day.today { box-shadow: inset 0 0 0 1px var(--brand-600); font-weight: 700; }
    .mu-day.on { background: var(--brand-600); color: #fff; font-weight: 700; }
    .mu-label { font-size: 11px; font-weight: 650; letter-spacing: 0.07em; text-transform: uppercase; color: var(--ink-500); margin: 12px 0 6px; }
    .mu-slots { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .mu-slot { height: 38px; border-radius: 8px; border: 1px solid var(--line-strong); background: var(--surface-sunk); font-size: 13px; font-weight: 600; color: var(--ink-900); cursor: pointer; font-family: inherit; }
    .mu-slot.on { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
    .mu-note { margin: 10px 0 0; padding: 10px 12px; border-radius: 8px; font-size: 12.5px; background: var(--warning-bg); color: var(--warning); }
    .mu-error { margin: 10px 0 0; padding: 10px 12px; border-radius: 8px; font-size: 12.5px; background: var(--danger-bg); color: var(--danger); }
</style>
@endpush

@push('scripts')
<script>
window.FMMeetup = (function () {
    const API = 'https://fati-api.alertaraqc.com/api';
    let hours = null;
    let resolveWith = null;
    let picked = { date: null, time: null };

    function token() {
        return document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    }

    const pad = (n) => String(n).padStart(2, '0');

    /** The store's wall clock right now, as plain parts, in the store's own zone. */
    function nowInStore() {
        const parts = new Intl.DateTimeFormat('en-US', {
            timeZone: hours.timezone || 'Asia/Manila', hourCycle: 'h23',
            year: 'numeric', month: 'numeric', day: 'numeric', hour: 'numeric', minute: 'numeric',
        }).formatToParts(new Date()).reduce((o, p) => (o[p.type] = p.value, o), {});
        return { y: Number(parts.year), m: Number(parts.month), d: Number(parts.day), hh: Number(parts.hour), mm: Number(parts.minute) };
    }

    function minutesOf(hhmm) {
        const [h, m] = String(hhmm || '00:00').split(':').map(Number);
        return h * 60 + (m || 0);
    }

    /** The start times on a day, every slot_minutes from opening, still ahead of now; the last starts before closing. */
    function slotsOn(y, m, d, now) {
        const open = minutesOf(hours.open_time), close = minutesOf(hours.close_time), step = Number(hours.slot_minutes) || 30;
        const slots = [];
        for (let t = open; t < close; t += step) {
            const future = (y > now.y) || (y === now.y && (m > now.m || (m === now.m && (d > now.d || (d === now.d && t > now.hh * 60 + now.mm)))));
            if (future) slots.push(t);
        }
        return slots;
    }

    /** In this month, not yet past, a day the store opens, with a slot left. */
    function isBookable(y, m, d, now) {
        if (y !== now.y || m !== now.m) return false;
        if (d < now.d) return false;
        const jsDay = new Date(y, m - 1, d).getDay();
        const isoDay = jsDay === 0 ? 7 : jsDay;
        const openDays = Array.isArray(hours.open_days) && hours.open_days.length ? hours.open_days.map(Number) : [1, 2, 3, 4, 5, 6];
        if (!openDays.includes(isoDay)) return false;
        return slotsOn(y, m, d, now).length > 0;
    }

    function daysLabel() {
        const names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        const days = (Array.isArray(hours.open_days) ? hours.open_days.map(Number) : []).sort((a, b) => a - b);
        if (days.length === 7) return 'Every day';
        if (days.length > 2 && days[days.length - 1] - days[0] === days.length - 1) return `${names[days[0] - 1]}-${names[days[days.length - 1] - 1]}`;
        return days.map(d => names[d - 1]).join(', ');
    }

    function clock(minutes) {
        const h = Math.floor(minutes / 60), m = minutes % 60;
        const suffix = h >= 12 ? 'PM' : 'AM';
        return `${((h + 11) % 12) + 1}:${pad(m)} ${suffix}`;
    }

    async function loadHours() {
        if (hours) return hours;
        const response = await fetch(`${API}/store/hours`, { headers: { 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json' } });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
        hours = payload.data || {};
        return hours;
    }

    function render() {
        const now = nowInStore();
        const monthName = new Date(now.y, now.m - 1, 1).toLocaleDateString([], { month: 'long', year: 'numeric' });
        const daysInMonth = new Date(now.y, now.m, 0).getDate();
        const leading = new Date(now.y, now.m - 1, 1).getDay(); // Sunday first, like a wall calendar
        const week = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];

        let html = `<p class="mu-month">${monthName}</p><div class="mu-week">${week.map(w => `<span>${w}</span>`).join('')}</div><div class="mu-grid">`;
        for (let i = 0; i < leading; i++) html += '<span></span>';
        let anyBookable = false;
        for (let d = 1; d <= daysInMonth; d++) {
            const ok = isBookable(now.y, now.m, d, now);
            anyBookable = anyBookable || ok;
            const cls = ['mu-day', d === now.d ? 'today' : '', picked.date === d ? 'on' : ''].join(' ');
            html += `<button type="button" class="${cls}" ${ok ? '' : 'disabled'} onclick="FMMeetup.pickDay(${d})">${d}</button>`;
        }
        html += '</div>';

        if (picked.date) {
            const label = new Date(now.y, now.m - 1, picked.date).toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric' });
            const slots = slotsOn(now.y, now.m, picked.date, now);
            html += `<p class="mu-label">Time · ${label}</p><div class="mu-slots">`;
            html += slots.map(t => `<button type="button" class="mu-slot ${picked.time === t ? 'on' : ''}" onclick="FMMeetup.pickTime(${t})">${clock(t)}</button>`).join('');
            html += '</div>';
        } else if (!anyBookable) {
            html += `<p class="mu-note"><b>No slots left this month.</b> Every open day in ${monthName} has passed. Meet-ups can only be booked within the current month.</p>`;
        }

        document.getElementById('meetupHours').textContent = `Store hours: ${daysLabel()} · ${hours.hours_label || (clock(minutesOf(hours.open_time)) + ' - ' + clock(minutesOf(hours.close_time)))}`;
        document.getElementById('meetupBody').innerHTML = html;
        document.getElementById('meetupSave').disabled = !(picked.date && picked.time !== null);
    }

    async function pick({ current } = {}) {
        picked = { date: null, time: null };
        document.getElementById('meetupBody').innerHTML = '<p class="cell-sub" style="padding: 20px 0; text-align: center;"><span class="loading-spinner"></span></p>';
        document.getElementById('meetupSave').disabled = true;
        document.getElementById('meetupModal').style.display = 'flex';

        return new Promise(async (resolve) => {
            resolveWith = resolve;
            try {
                await loadHours();
                // Start from the booked slot while it is still bookable.
                if (current) {
                    const m = String(current).match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
                    const now = nowInStore();
                    if (m && Number(m[1]) === now.y && Number(m[2]) === now.m && isBookable(now.y, now.m, Number(m[3]), now)) {
                        const t = Number(m[4]) * 60 + Number(m[5]);
                        if (slotsOn(now.y, now.m, Number(m[3]), now).includes(t)) picked = { date: Number(m[3]), time: t };
                    }
                }
                render();
            } catch (error) {
                document.getElementById('meetupBody').innerHTML = `<p class="mu-error">Could not load the store hours: ${String(error.message).replace(/</g, '&lt;')}</p>
                    <button class="fm-btn ghost sm" style="margin-top: 10px;" onclick="FMMeetup.retry()">Try again</button>`;
            }
        });
    }

    function retry() { hours = null; const r = resolveWith; pick({}).then(r); }
    function pickDay(d) { picked = { date: d, time: null }; render(); }
    function pickTime(t) { picked.time = t; render(); }

    function finish(value) {
        document.getElementById('meetupModal').style.display = 'none';
        const r = resolveWith; resolveWith = null;
        if (r) r(value);
    }

    function cancel() { finish(null); }

    function save() {
        if (!picked.date || picked.time === null) return;
        const now = nowInStore();
        // Wall-clock time in the store's own zone, which is how the server reads it.
        finish(`${now.y}-${pad(now.m)}-${pad(picked.date)} ${pad(Math.floor(picked.time / 60))}:${pad(picked.time % 60)}:00`);
    }

    return { pick, cancel, save, pickDay, pickTime, retry };
})();
</script>
@endpush
