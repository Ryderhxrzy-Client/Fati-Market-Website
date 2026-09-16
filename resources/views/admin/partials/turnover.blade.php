{{--
    Marking an item acquired, the way the counter actually does it.

    Receiving an item is two photographs - the item in hand, and the seller
    holding their cash - and the desk computer is rarely where a usable camera
    is. So this offers both: carry on with a phone, by scanning a QR that
    opens that item's turnover page already authorised, or do it here with
    files picked from the computer.

    Either way the selling price and the status the item lands in can be set
    in the same breath, and both routes end in the same server-side sequence,
    so a turnover done on a phone is indistinguishable from one done here.

    Include once per page, then call openTurnover(itemId, onDone).
--}}

<div id="turnoverModal"
     onclick="if (event.target === this) closeTurnover()"
     style="display: none; position: fixed; inset: 0; background: rgba(17,24,39,0.82); z-index: 90; align-items: center; justify-content: center; padding: 24px;">
    <div style="background: white; border-radius: 14px; max-width: 560px; width: 100%; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 18px; border-bottom: 1px solid var(--line, #e5e7eb);">
            <div>
                <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--ink-900, #111827);">Mark as acquired</h4>
                <p id="turnoverSubtitle" style="margin: 2px 0 0; font-size: 12.5px; color: var(--ink-500, #6b7280);"></p>
            </div>
            <button onclick="closeTurnover()" style="background: none; border: none; font-size: 22px; line-height: 1; cursor: pointer; color: var(--ink-500, #6b7280);">&times;</button>
        </div>

        <div id="turnoverTabs" style="display: flex; gap: 6px; padding: 12px 18px 0;">
            <button class="fm-btn sm" id="turnoverTabPhone" onclick="turnoverTab('phone')" style="flex: 1;">
                <i class="fas fa-mobile-screen"></i>Continue on phone
            </button>
            <button class="fm-btn sm" id="turnoverTabHere" onclick="turnoverTab('here')" style="flex: 1;">
                <i class="fas fa-desktop"></i>Do it here
            </button>
        </div>

        <div id="turnoverBody" style="padding: 16px 18px 20px; overflow-y: auto;"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    const TV_API = @json(rtrim(config('services.fati.url', 'https://fati-api.alertaraqc.com/api'), '/'));
    const TV_HANDOFF_URL = @json(route('admin.turnover.handoff'));
    const TV_COMPLETE_URL = @json(url('/counter/turnover'));
    const TV_STATUSES = @json(\App\Services\TurnoverService::STATUSES);

    let tvItem = null;
    let tvTab = 'phone';
    let tvOnDone = null;
    let tvBusy = false;
    let tvHandoff = null;
    let tvPoll = null;
    let tvCountdown = null;
    let tvStatusTouched = false;
    const tvPhotos = { item: null, payout: null };

    function tvToken() {
        return document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    }

    function tvCsrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function tvEscape(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function tvAttr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function tvPeso(amount) {
        if (amount === null || amount === undefined || amount === '' || !isFinite(Number(amount))) return '—';
        return '₱' + Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function tvNotify(message, tone) {
        if (typeof window.showToast === 'function') window.showToast(message, tone);
        else if (tone === 'error') alert(message);
    }

    // ── Opening and closing ──────────────────────────────────────────────

    window.openTurnover = async function (itemId, onDone) {
        tvOnDone = typeof onDone === 'function' ? onDone : null;
        tvItem = null;
        tvHandoff = null;
        tvStatusTouched = false;
        tvPhotos.item = null;
        tvPhotos.payout = null;
        tvTab = 'phone';

        document.getElementById('turnoverModal').style.display = 'flex';
        document.getElementById('turnoverTabs').style.display = 'flex';
        document.getElementById('turnoverSubtitle').textContent = '';
        document.getElementById('turnoverBody').innerHTML =
            '<p style="font-size: 13px; color: var(--ink-500, #6b7280);">Loading the item…</p>';

        try {
            const response = await fetch(`${TV_API}/items/${itemId}`, {
                headers: { 'Authorization': `Bearer ${tvToken()}`, 'Accept': 'application/json' },
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            tvItem = payload.data || {};
            renderTurnover();
        } catch (error) {
            document.getElementById('turnoverBody').innerHTML =
                `<p style="font-size: 13px; color: var(--danger, #b91c1c);">Could not load the item: ${tvEscape(error.message)}</p>`;
        }
    };

    window.closeTurnover = function () {
        document.getElementById('turnoverModal').style.display = 'none';
        stopWatching();
        tvItem = null;
        tvHandoff = null;
    };

    window.turnoverTab = function (tab) {
        if (tvTab === tab) return;
        tvTab = tab;
        stopWatching();
        renderTurnover();
    };

    function stopWatching() {
        if (tvPoll) { clearInterval(tvPoll); tvPoll = null; }
        if (tvCountdown) { clearInterval(tvCountdown); tvCountdown = null; }
    }

    // ── The panel ────────────────────────────────────────────────────────

    function renderTurnover() {
        const item = tvItem || {};
        const status = String(item.status || '').toLowerCase();
        const received = !!item.is_turnover_verified || ['acquired', 'public', 'reserved', 'sold'].includes(status);

        document.getElementById('turnoverSubtitle').textContent =
            `${item.title || ('Item #' + item.item_id)} · ${item.seller_email || 'seller'}`;

        for (const [name, element] of [['phone', 'turnoverTabPhone'], ['here', 'turnoverTabHere']]) {
            const button = document.getElementById(element);
            button.className = 'fm-btn sm ' + (tvTab === name ? 'primary' : 'ghost');
        }

        const body = document.getElementById('turnoverBody');

        if (status === 'sold' || status === 'rejected') {
            body.innerHTML = `<p style="font-size: 13px; color: var(--danger, #b91c1c); margin: 0;">
                This item is ${tvEscape(status)}, so there is nothing to receive.</p>`;
            return;
        }

        body.innerHTML = summaryHtml(item, received) + (tvTab === 'phone' ? phoneHtml() : manualHtml(item, received));

        if (tvTab === 'phone') startHandoff();
        else bindManual(received);
    }

    function summaryHtml(item, received) {
        const row = (label, value) => `
            <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 3px 0;">
                <span style="color: var(--ink-500, #6b7280);">${tvEscape(label)}</span>
                <span style="font-weight: 600;">${tvEscape(value)}</span>
            </div>`;

        return `
            <div style="border: 1px solid var(--line, #e5e7eb); border-radius: 10px; padding: 12px; margin-bottom: 14px; background: var(--surface-sunk, #f9fafb);">
                ${row('Asking price', tvPeso(item.seller_asking_price))}
                ${row('Agreed price', tvPeso(item.acquisition_price))}
                ${received ? row('Already received', 'Yes') : ''}
            </div>`;
    }

    // ── Carry on with a phone ────────────────────────────────────────────

    function phoneHtml() {
        return `
            <p style="font-size: 13px; color: var(--ink-700, #374151); margin: 0 0 12px;">
                Scan this with the phone at the counter. It opens this item's turnover screen, where the
                two photos are taken with the phone's camera.
            </p>
            <div id="turnoverQr" style="display: flex; align-items: center; justify-content: center; min-height: 236px; padding: 12px; border: 1px solid var(--line, #e5e7eb); border-radius: 12px;">
                <span class="loading-spinner"></span>
            </div>
            <p id="turnoverQrNote" style="font-size: 12px; color: var(--ink-500, #6b7280); margin: 10px 0 0; text-align: center;"></p>
            <div id="turnoverLink" style="margin-top: 10px;"></div>
            <div id="turnoverWatch" style="margin-top: 14px; font-size: 13px; color: var(--ink-600, #4b5563); display: flex; align-items: center; gap: 8px;">
                <span class="loading-spinner"></span><span>Waiting for the phone to finish…</span>
            </div>`;
    }

    async function startHandoff() {
        const host = document.getElementById('turnoverQr');

        // Coming back to this tab shows the code already on the counter's
        // screen rather than minting a second one for the same item.
        if (tvHandoff) {
            drawHandoff(tvHandoff);
            return;
        }

        try {
            const response = await fetch(TV_HANDOFF_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': tvCsrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ item_id: tvItem.item_id }),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            tvHandoff = payload;
            drawHandoff(payload);
        } catch (error) {
            host.innerHTML = `<p style="font-size: 13px; color: var(--danger, #b91c1c); margin: 0;">
                Could not create the counter link: ${tvEscape(error.message)}</p>`;
            document.getElementById('turnoverWatch').style.display = 'none';
        }
    }

    function drawHandoff(handoff) {
        const host = document.getElementById('turnoverQr');
        host.innerHTML = '';

        // The QR itself. Without the library the link is still usable - it can
        // be typed, or sent to the phone some other way - so the panel never
        // becomes a dead end.
        if (typeof window.QRCode === 'function') {
            new window.QRCode(host, {
                text: handoff.url,
                width: 220,
                height: 220,
                correctLevel: window.QRCode.CorrectLevel.M,
            });
        } else {
            host.innerHTML = `<p style="font-size: 12.5px; color: var(--ink-500, #6b7280); margin: 0; text-align: center;">
                The QR drawing library did not load. Open the link below on the phone instead.</p>`;
        }

        document.getElementById('turnoverLink').innerHTML = `
            <div style="display: flex; gap: 6px;">
                <input class="fm-input" id="turnoverUrl" readonly value="${tvAttr(handoff.url)}" style="font-size: 12px;">
                <button class="fm-btn ghost sm" onclick="turnoverCopy()">Copy</button>
            </div>`;

        const left = handoff.expires_at
            ? Math.round((new Date(handoff.expires_at).getTime() - Date.now()) / 1000)
            : (handoff.expires_in || 1800);

        startCountdown(Math.max(0, left));
        watchForPhone();
    }

    window.turnoverCopy = function () {
        const field = document.getElementById('turnoverUrl');
        if (!field) return;
        field.select();
        if (navigator.clipboard?.writeText) {
            navigator.clipboard.writeText(field.value).then(() => tvNotify('Link copied', 'success'));
        } else {
            document.execCommand('copy');
        }
    };

    function startCountdown(seconds) {
        const note = document.getElementById('turnoverQrNote');
        let left = seconds;

        const tick = () => {
            if (left <= 0) {
                stopWatching();
                note.textContent = 'This code has expired. Close and reopen to get a new one.';
                return;
            }
            const minutes = Math.floor(left / 60);
            note.textContent = `The code works for ${minutes > 0 ? minutes + ' more minute' + (minutes === 1 ? '' : 's') : 'less than a minute'}.`;
            left -= 1;
        };

        tick();
        tvCountdown = setInterval(tick, 1000);
    }

    /**
     * The phone finishing is the only signal that matters here, and it comes
     * from the item itself rather than from anything this page can hear, so
     * the item is re-read every few seconds until it says it was received.
     */
    function watchForPhone() {
        tvPoll = setInterval(async () => {
            try {
                const response = await fetch(`${TV_API}/items/${tvItem.item_id}`, {
                    headers: { 'Authorization': `Bearer ${tvToken()}`, 'Accept': 'application/json' },
                });
                if (!response.ok) return;

                const payload = await response.json().catch(() => ({}));
                const item = payload.data || {};

                if (!item.is_turnover_verified) return;

                stopWatching();
                tvItem = item;
                finish(item, []);
            } catch (error) {
                // A dropped request is not an answer; the next tick asks again.
            }
        }, 4000);
    }

    // ── Do it here ───────────────────────────────────────────────────────

    function manualHtml(item, received) {
        const needsAgreed = !received && (item.acquisition_price === null || item.acquisition_price === undefined);

        const statusOptions = TV_STATUSES.map(status =>
            `<option value="${tvAttr(status)}"${received && String(item.status).toLowerCase() === status ? ' selected' : ''}>${tvEscape(status.charAt(0).toUpperCase() + status.slice(1))}</option>`
        ).join('');

        return `
            ${received ? `<p style="font-size: 13px; background: var(--info-bg, #dbeafe); color: var(--info, #1d4ed8); padding: 10px 12px; border-radius: 10px; margin: 0 0 14px;">
                This item is already in the store. Its selling price and status can still be corrected.</p>` : ''}

            ${needsAgreed ? `
                <label class="fm-label">Agreed price (₱)</label>
                <p style="font-size: 12px; color: var(--ink-500, #6b7280); margin: -2px 0 6px;">No price was agreed in the chat. Enter what the store pays the seller.</p>
                <input id="tvAcquisition" class="fm-input" type="number" step="0.01" min="0" placeholder="e.g. 180.00" style="margin-bottom: 14px;">` : ''}

            ${received ? '' : `
                <label class="fm-label">Proof: the item you received</label>
                <img id="tvItemPreview" alt="" style="display: none; width: 100%; height: 150px; object-fit: cover; border-radius: 10px; margin-bottom: 6px;">
                <input id="tvItemPhoto" class="fm-input" type="file" accept="image/*" capture="environment" style="margin-bottom: 12px;">

                <label class="fm-label">Proof: the seller being paid</label>
                <img id="tvPayoutPreview" alt="" style="display: none; width: 100%; height: 150px; object-fit: cover; border-radius: 10px; margin-bottom: 6px;">
                <input id="tvPayoutPhoto" class="fm-input" type="file" accept="image/*" capture="environment" style="margin-bottom: 14px;">`}

            <label class="fm-label">Public selling price (optional)</label>
            <input id="tvPublicPrice" class="fm-input" type="number" step="0.01" min="0" placeholder="Leave blank to price it later"
                   value="${tvAttr(received ? (item.public_price || '') : '')}">
            <p id="tvMarkup" style="font-size: 12px; color: var(--ink-500, #6b7280); margin: 6px 0 12px; display: none;"></p>

            <label class="fm-label">Status ${received ? '' : 'after turnover'}</label>
            <select id="tvStatus" class="fm-select" style="margin-bottom: 14px;">${statusOptions}</select>

            ${received ? '' : `
                <label class="fm-label">Notes (optional)</label>
                <input id="tvNotes" class="fm-input" type="text" maxlength="500" placeholder="e.g. charger missing" style="margin-bottom: 14px;">`}

            <button class="fm-btn primary" id="tvSubmit" style="width: 100%;" onclick="turnoverSubmit()">
                ${received ? 'Save price &amp; status' : 'Mark acquired &amp; seller paid'}
            </button>`;
    }

    function bindManual(received) {
        const price = document.getElementById('tvPublicPrice');
        const status = document.getElementById('tvStatus');

        tvStatusTouched = received;

        status.addEventListener('change', () => { tvStatusTouched = true; refreshManual(received); });
        price.addEventListener('input', () => refreshManual(received));
        document.getElementById('tvAcquisition')?.addEventListener('input', () => refreshManual(received));

        if (!received) {
            bindPhoto('tvItemPhoto', 'tvItemPreview', 'item', received);
            bindPhoto('tvPayoutPhoto', 'tvPayoutPreview', 'payout', received);
        }

        refreshManual(received);
    }

    function bindPhoto(inputId, previewId, slot, received) {
        const input = document.getElementById(inputId);

        input.addEventListener('change', async () => {
            const file = input.files && input.files[0];
            if (!file) { tvPhotos[slot] = null; refreshManual(received); return; }

            const shrunk = await shrink(file);
            tvPhotos[slot] = shrunk.blob;

            const preview = document.getElementById(previewId);
            if (shrunk.url) { preview.src = shrunk.url; preview.style.display = 'block'; }

            refreshManual(received);
        });
    }

    /** A phone photograph is several megabytes; the counter only needs proof. */
    function shrink(file) {
        return new Promise((resolve) => {
            const reader = new FileReader();

            reader.onload = () => {
                const image = new Image();

                image.onload = () => {
                    const max = 1400;
                    const scale = Math.min(1, max / Math.max(image.width, image.height));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.round(image.width * scale);
                    canvas.height = Math.round(image.height * scale);
                    canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(
                        (blob) => resolve({ blob: blob || file, url: canvas.toDataURL('image/jpeg', 0.7) }),
                        'image/jpeg',
                        0.82,
                    );
                };

                image.onerror = () => resolve({ blob: file, url: null });
                image.src = reader.result;
            };

            reader.onerror = () => resolve({ blob: file, url: null });
            reader.readAsDataURL(file);
        });
    }

    function typedPrice() {
        const raw = (document.getElementById('tvPublicPrice')?.value || '').trim();
        if (!raw) return null;
        const value = Number(raw);
        return isFinite(value) && value > 0 ? value : null;
    }

    function refreshManual(received) {
        const price = typedPrice();
        const status = document.getElementById('tvStatus');
        const button = document.getElementById('tvSubmit');

        // Left alone, the status follows the price: a selling price publishes.
        if (!tvStatusTouched) status.value = price === null ? 'acquired' : 'public';

        const agreed = Number(tvItem.acquisition_price ?? document.getElementById('tvAcquisition')?.value ?? NaN);
        const markup = document.getElementById('tvMarkup');

        if (price !== null && isFinite(agreed) && agreed > 0) {
            markup.textContent = `Markup ${tvPeso(price - agreed)} over the ${tvPeso(agreed)} paid to the seller.`;
            markup.style.display = 'block';
        } else {
            markup.style.display = 'none';
        }

        const hasProof = received || (tvPhotos.item && tvPhotos.payout);
        const needsPrice = status.value === 'public' && price === null;

        button.disabled = tvBusy || !hasProof || needsPrice;
        button.textContent = needsPrice ? 'A published item needs a price'
            : received ? 'Save price & status'
            : !hasProof ? 'Attach both proof photos first'
            : status.value === 'public' ? 'Mark acquired & publish'
            : status.value === 'acquired' ? 'Mark acquired & seller paid'
            : 'Mark acquired as ' + status.value;
    }

    window.turnoverSubmit = async function () {
        if (tvBusy || !tvItem) return;

        const button = document.getElementById('tvSubmit');
        const status = String(tvItem.status || '').toLowerCase();
        const received = !!tvItem.is_turnover_verified || ['acquired', 'public', 'reserved', 'sold'].includes(status);

        tvBusy = true;
        button.disabled = true;
        button.textContent = 'Saving…';

        const body = new FormData();
        const acquisition = document.getElementById('tvAcquisition');
        const notes = document.getElementById('tvNotes');
        const price = document.getElementById('tvPublicPrice');

        if (acquisition && acquisition.value.trim()) body.append('acquisition_price', acquisition.value.trim());
        if (notes && notes.value.trim()) body.append('notes', notes.value.trim());
        if (price && price.value.trim()) body.append('public_price', price.value.trim());
        body.append('status', document.getElementById('tvStatus').value);

        if (tvPhotos.item) body.append('turnover_photo', tvPhotos.item, 'item-received.jpg');
        if (tvPhotos.payout) body.append('payout_photo', tvPhotos.payout, 'seller-paid.jpg');

        try {
            const response = await fetch(`${TV_COMPLETE_URL}/${tvItem.item_id}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': tvCsrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            tvBusy = false;
            finish(payload.item || tvItem, payload.warnings || [], received);
        } catch (error) {
            tvBusy = false;
            tvNotify(error.message, 'error');
            refreshManual(received);
        }
    };

    // ── Done, either way ─────────────────────────────────────────────────

    function finish(item, warnings, wasReceived) {
        stopWatching();

        const status = String(item.status || '').toLowerCase();

        document.getElementById('turnoverTabs').style.display = 'none';
        document.getElementById('turnoverBody').innerHTML = `
            <div style="text-align: center; padding: 10px 0 4px;">
                <i class="fas fa-circle-check" style="font-size: 38px; color: var(--success, #166534);"></i>
                <h4 style="margin: 12px 0 4px; font-size: 16px; font-weight: 700;">
                    ${wasReceived ? 'Saved' : status === 'public' ? 'Acquired and published' : 'Item acquired'}</h4>
                <p style="margin: 0; font-size: 13px; color: var(--ink-600, #4b5563);">
                    ${wasReceived
                        ? 'The item now reads as ' + tvEscape(status || 'updated') + '.'
                        : 'The item is in the store and the seller has been paid'
                          + (status === 'public' ? ', and the listing is live in the catalog.' : '.')}</p>
            </div>
            ${(warnings || []).map(line => `<p style="font-size: 12.5px; background: var(--warning-bg, #fef3c7); color: var(--warning, #92400e); padding: 9px 11px; border-radius: 8px; margin: 10px 0 0;">${tvEscape(line)}</p>`).join('')}
            <button class="fm-btn primary" style="width: 100%; margin-top: 16px;" onclick="closeTurnover()">Done</button>`;

        tvNotify(wasReceived ? 'Saved' : 'Item marked acquired', 'success');

        if (tvOnDone) tvOnDone(item);
    }
})();
</script>
@endpush
