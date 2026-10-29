{{--
    Standalone Date-Only Modal Picker for Pick-up / Meet-up on the website.
    No browser date picker (<input type="date">).
    Uses the app's custom calendar grid:
    - Month navigation (prev/next month)
    - Store open days: Monday to Saturday (8:00 AM – 6:00 PM)
    - Sundays and past dates disabled
    - Strictly DATE-ONLY (no time slots)
--}}

<div id="pickupDateModal" onclick="if (event.target === this) FMPickupDate.cancel()"
     style="display: none; position: fixed; inset: 0; background: rgba(12, 48, 33, 0.6); z-index: 100; align-items: center; justify-content: center; padding: 24px;">
    <div style="background: var(--surface, #ffffff); border-radius: 16px; max-width: 420px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <div style="padding: 18px 20px 0; display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--line, #e5e7eb); padding-bottom: 14px;">
            <div>
                <h4 id="pickupDateModalTitle" style="margin: 0; font-size: 16px; font-weight: 700; color: var(--ink-900, #111827);">Select pick-up date</h4>
                <p style="margin: 4px 0 0; font-size: 12.5px; color: var(--ink-500, #6b7280);">Store open: Mon – Sat · 8:00 AM – 6:00 PM (Closed Sunday)</p>
            </div>
            <button type="button" onclick="FMPickupDate.cancel()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: var(--ink-500, #6b7280); line-height: 1;">&times;</button>
        </div>

        <div id="pickupDateModalBody" style="padding: 16px 20px 6px;"></div>

        <div style="display: flex; gap: 10px; padding: 14px 20px 18px; border-top: 1px solid var(--line, #e5e7eb);">
            <button type="button" class="fm-btn ghost" style="flex: 1;" onclick="FMPickupDate.cancel()">Cancel</button>
            <button type="button" class="fm-btn primary" style="flex: 1;" id="pickupDateModalSave" onclick="FMPickupDate.save()" disabled>Confirm date</button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .pud-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .pud-nav-title { font-size: 15px; font-weight: 700; color: var(--ink-900, #111827); margin: 0; }
    .pud-nav-btn { background: var(--surface, #fff); border: 1px solid var(--line-strong, #d1d5db); border-radius: 8px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; color: var(--ink-700, #374151); transition: all 0.15s; }
    .pud-nav-btn:hover:not(:disabled) { background: var(--surface-sunk, #f3f4f6); border-color: var(--ink-400, #9ca3af); }
    .pud-nav-btn:disabled { opacity: 0.3; cursor: not-allowed; }
    .pud-week { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; margin-bottom: 6px; }
    .pud-week span { text-align: center; font-size: 11.5px; font-weight: 600; color: var(--ink-500, #6b7280); padding: 2px 0; }
    .pud-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
    .pud-day { aspect-ratio: 1; border: none; background: none; border-radius: 50%; font-size: 13.5px; color: var(--ink-900, #111827); cursor: pointer; font-family: inherit; font-weight: 500; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
    .pud-day:hover:not(:disabled) { background: #d1fae5; }
    .pud-day:disabled { color: var(--ink-400, #9ca3af); opacity: 0.28; cursor: not-allowed; text-decoration: line-through; }
    .pud-day.today { box-shadow: inset 0 0 0 2px var(--brand, #059669); font-weight: 700; }
    .pud-day.on { background: var(--brand, #059669) !important; color: #ffffff !important; font-weight: 700; }
    .pud-preview { margin-top: 14px; padding: 12px 14px; border-radius: 10px; background: #ecfdf5; border: 1px solid #a7f3d0; font-size: 13px; color: #065f46; }
</style>
@endpush

@push('scripts')
<script>
window.FMPickupDate = (function () {
    let resolveWith = null;
    let selectedDate = null; // 'YYYY-MM-DD'
    let currentYear = null;
    let currentMonth = null; // 0-indexed
    let earliestDate = null; // Date object

    const pad = (n) => String(n).padStart(2, '0');

    function calculateEarliestDate() {
        const now = new Date();
        // Cutoff is 18:00 (6:00 PM). If after 6PM or if Sunday, earliest is next open day.
        const pastCutoff = now.getHours() >= 18;
        let cand = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        if (pastCutoff || cand.getDay() === 0) {
            cand.setDate(cand.getDate() + 1);
        }
        // If Sunday, roll to Monday
        while (cand.getDay() === 0) {
            cand.setDate(cand.getDate() + 1);
        }
        return cand;
    }

    function isSelectable(y, m, d) {
        const dt = new Date(y, m, d);
        // Closed Sunday
        if (dt.getDay() === 0) return false;
        // Cannot be before earliest selectable date
        if (dt < earliestDate) return false;
        return true;
    }

    function render() {
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const leadingBlanks = new Date(currentYear, currentMonth, 1).getDay(); // Sunday = 0
        const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const now = new Date();

        // Previous button enabled only if after earliest month
        const earliestYear = earliestDate.getFullYear();
        const earliestMonth = earliestDate.getMonth();
        const canGoPrev = (currentYear > earliestYear) || (currentYear === earliestYear && currentMonth > earliestMonth);
        const canGoNext = (currentYear < earliestYear + 1);

        let html = `
            <div class="pud-nav">
                <button type="button" class="pud-nav-btn" ${canGoPrev ? '' : 'disabled'} onclick="FMPickupDate.prevMonth()">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <h5 class="pud-nav-title">${monthNames[currentMonth]} ${currentYear}</h5>
                <button type="button" class="pud-nav-btn" ${canGoNext ? '' : 'disabled'} onclick="FMPickupDate.nextMonth()">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="pud-week">${weekDays.map(w => `<span>${w}</span>`).join('')}</div>
            <div class="pud-grid">`;

        for (let i = 0; i < leadingBlanks; i++) {
            html += '<span></span>';
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateStr = `${currentYear}-${pad(currentMonth + 1)}-${pad(d)}`;
            const ok = isSelectable(currentYear, currentMonth, d);
            const isToday = (currentYear === now.getFullYear() && currentMonth === now.getMonth() && d === now.getDate());
            const isSelected = (selectedDate === dateStr);

            const cls = ['pud-day', isToday ? 'today' : '', isSelected ? 'on' : ''].filter(Boolean).join(' ');
            html += `<button type="button" class="${cls}" ${ok ? '' : 'disabled'} onclick="FMPickupDate.selectDay('${dateStr}')">${d}</button>`;
        }
        html += '</div>';

        if (selectedDate) {
            const [sy, sm, sd] = selectedDate.split('-').map(Number);
            const selDateObj = new Date(sy, sm - 1, sd);
            const formatted = selDateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
            html += `
                <div class="pud-preview">
                    <div style="font-weight: 700; margin-bottom: 2px;">Selected: ${formatted}</div>
                    <div style="font-size: 12px; color: #047857;">Pick up at Ofelia's store during store hours (8:00 AM – 6:00 PM) on this day.</div>
                </div>`;
        }

        document.getElementById('pickupDateModalBody').innerHTML = html;
        document.getElementById('pickupDateModalSave').disabled = !selectedDate;
    }

    function pick({ current, title } = {}) {
        earliestDate = calculateEarliestDate();
        selectedDate = null;

        if (current) {
            const m = String(current).match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (m) {
                const cy = Number(m[1]), cm = Number(m[2]) - 1, cd = Number(m[3]);
                if (isSelectable(cy, cm, cd)) {
                    selectedDate = `${cy}-${pad(cm + 1)}-${pad(cd)}`;
                }
            }
        }

        // Default to earliestDate if current is invalid, in the past, or empty
        if (!selectedDate) {
            selectedDate = `${earliestDate.getFullYear()}-${pad(earliestDate.getMonth() + 1)}-${pad(earliestDate.getDate())}`;
        }

        const [initY, initM] = selectedDate.split('-').map(Number);
        currentYear = initY;
        currentMonth = initM - 1;

        if (title) {
            document.getElementById('pickupDateModalTitle').textContent = title;
        }

        document.getElementById('pickupDateModal').style.display = 'flex';
        render();

        return new Promise((resolve) => {
            resolveWith = resolve;
        });
    }

    function selectDay(dateStr) {
        selectedDate = dateStr;
        render();
    }

    function prevMonth() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        render();
    }

    function nextMonth() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        render();
    }

    function finish(value) {
        document.getElementById('pickupDateModal').style.display = 'none';
        const resolve = resolveWith;
        resolveWith = null;
        if (resolve) resolve(value);
    }

    function cancel() {
        finish(null);
    }

    function save() {
        if (!selectedDate) return;
        finish(selectedDate);
    }

    return { pick, cancel, save, selectDay, prevMonth, nextMonth };
})();
</script>
@endpush

