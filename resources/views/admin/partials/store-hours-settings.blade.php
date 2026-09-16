<section class="fm-card" aria-labelledby="hours-heading">
    <div class="fm-card-head">
        <div>
            <h4 id="hours-heading">Store hours &amp; booking slots</h4>
            <p class="cell-sub" style="margin-top: 2px;">Shown to student sellers, and used to validate every meet-up booking.</p>
        </div>
        <span class="fm-badge brand" id="hoursLabel" style="display: none;"></span>
    </div>
    <div class="fm-card-body">
        <div id="hoursState" style="font-size: 13px; color: var(--ink-500);">
            <span class="loading-spinner" style="vertical-align: middle; margin-right: 8px;"></span>Loading store hours…
        </div>

        <form id="hoursForm" class="space-y-4" style="display: none;" onsubmit="saveStoreHours(event)">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fm-label" for="openTime">Opening time</label>
                    <input id="openTime" type="time" class="fm-input" required>
                </div>
                <div>
                    <label class="fm-label" for="closeTime">Closing time</label>
                    <input id="closeTime" type="time" class="fm-input" required>
                </div>
            </div>

            <div>
                <span class="fm-label">Open days</span>
                <div id="openDays" class="flex flex-wrap gap-2"></div>
            </div>

            <div>
                <label class="fm-label" for="slotMinutes">Booking slot length (minutes)</label>
                <input id="slotMinutes" type="number" min="5" max="240" step="5" class="fm-input" required>
                <p class="cell-sub" style="margin-top: 5px;">5 to 240 minutes. A booking must finish before closing time.</p>
            </div>

            <div class="flex items-center justify-between gap-3 pt-2" style="border-top: 1px solid var(--line);">
                <p id="hoursMessage" class="cell-sub" style="margin: 0;"></p>
                <button type="submit" id="hoursSave" class="fm-btn primary">
                    <i class="fas fa-save"></i>Save store hours
                </button>
            </div>
        </form>
    </div>
</section>

@push('styles')
<style>
    .day-chip {
        padding: 6px 12px;
        border-radius: 999px;
        border: 1px solid var(--line-strong);
        background: var(--surface);
        color: var(--ink-700);
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .day-chip.on { background: var(--brand-600); border-color: var(--brand-600); color: #fff; }
    .day-chip:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const API = 'https://fati-api.alertaraqc.com/api';
    const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    const headers = {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    };
    const DAYS = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    let openDays = new Set();
    let saving = false;

    function el(id) { return document.getElementById(id); }

    function renderDays() {
        el('openDays').innerHTML = DAYS.map((label, i) => {
            const day = i + 1;
            return `<button type="button" class="day-chip ${openDays.has(day) ? 'on' : ''}" data-day="${day}" ${saving ? 'disabled' : ''}>${label}</button>`;
        }).join('');

        el('openDays').querySelectorAll('.day-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                const day = Number(chip.dataset.day);
                if (openDays.has(day)) openDays.delete(day); else openDays.add(day);
                renderDays();
            });
        });
    }

    function fill(data) {
        el('openTime').value = String(data.open_time || '08:00').slice(0, 5);
        el('closeTime').value = String(data.close_time || '17:00').slice(0, 5);
        el('slotMinutes').value = data.slot_minutes || 30;
        openDays = new Set((Array.isArray(data.open_days) && data.open_days.length ? data.open_days : [1, 2, 3, 4, 5, 6]).map(Number));
        renderDays();

        const label = el('hoursLabel');
        if (data.hours_label) {
            label.textContent = data.hours_label;
            label.style.display = 'inline-flex';
        }
    }

    async function load() {
        try {
            const response = await fetch(`${API}/admin/settings/store-hours`, { headers });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            fill(payload.data || {});
            el('hoursState').style.display = 'none';
            el('hoursForm').style.display = 'block';
        } catch (error) {
            el('hoursState').innerHTML = `
                <p style="color: var(--danger); margin: 0 0 10px;">Could not load the store hours: ${error.message}</p>
                <button type="button" class="fm-btn ghost sm" onclick="location.reload()">Retry</button>
            `;
        }
    }

    window.saveStoreHours = async function (event) {
        event.preventDefault();
        if (saving) return;

        const open = el('openTime').value;
        const close = el('closeTime').value;
        const minutes = Number(el('slotMinutes').value);
        const message = el('hoursMessage');
        message.style.color = 'var(--danger)';

        if (!open || !close || open >= close) { message.textContent = 'Closing time must be after opening time.'; return; }
        if (openDays.size === 0) { message.textContent = 'Choose at least one open day.'; return; }
        if (!(minutes >= 5 && minutes <= 240)) { message.textContent = 'Slot length must be from 5 to 240 minutes.'; return; }

        saving = true;
        el('hoursSave').disabled = true;
        message.textContent = 'Saving…';
        message.style.color = 'var(--ink-500)';

        try {
            const response = await fetch(`${API}/admin/settings/store-hours`, {
                method: 'PUT',
                headers,
                body: JSON.stringify({
                    open_time: open,
                    close_time: close,
                    open_days: [...openDays].sort((a, b) => a - b),
                    slot_minutes: minutes,
                }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
                throw new Error(errors || payload.message || `HTTP ${response.status}`);
            }

            fill(payload.data || {});
            message.textContent = 'Store hours saved.';
            message.style.color = 'var(--success)';
            if (typeof showToast === 'function') showToast('Store hours saved', 'success');
        } catch (error) {
            message.textContent = error.message;
            message.style.color = 'var(--danger)';
        } finally {
            saving = false;
            el('hoursSave').disabled = false;
            renderDays();
        }
    };

    load();
})();
</script>
@endpush
