{{--
    Completing a handover, the way the counter actually does it.

    The twin of the turnover panel. Completing an order credits the buyer's
    reward points and is meant to be photographed - the buyer receiving the
    item - and the desk computer rarely has a usable camera. So this offers
    both: carry on with a phone by scanning a QR that opens this one order's
    completion screen, or do it here with a file from this computer.

    Include once per page, then call openPickup(order, onDone).
--}}

<div id="pickupModal"
     onclick="if (event.target === this) closePickup()"
     style="display: none; position: fixed; inset: 0; background: rgba(17,24,39,0.82); z-index: 90; align-items: center; justify-content: center; padding: 24px;">
    <div style="background: white; border-radius: 14px; max-width: 520px; width: 100%; max-height: 92vh; display: flex; flex-direction: column; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 18px; border-bottom: 1px solid var(--line, #e5e7eb);">
            <div>
                <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--ink-900, #111827);">Complete handover</h4>
                <p id="pickupSubtitle" style="margin: 2px 0 0; font-size: 12.5px; color: var(--ink-500, #6b7280);"></p>
            </div>
            <button onclick="closePickup()" style="background: none; border: none; font-size: 22px; line-height: 1; cursor: pointer; color: var(--ink-500, #6b7280);">&times;</button>
        </div>

        <div id="pickupTabs" style="display: flex; gap: 6px; padding: 12px 18px 0;">
            <button class="fm-btn sm" id="pickupTabPhone" onclick="pickupTab('phone')" style="flex: 1;">
                <i class="fas fa-mobile-screen"></i>Continue on phone
            </button>
            <button class="fm-btn sm" id="pickupTabHere" onclick="pickupTab('here')" style="flex: 1;">
                <i class="fas fa-desktop"></i>Do it here
            </button>
        </div>

        <div id="pickupBody" style="padding: 16px 18px 20px; overflow-y: auto;"></div>
    </div>
</div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    const PU_API = @json(rtrim(config('services.fati.url', 'https://fati-api.alertaraqc.com/api'), '/'));
    const PU_HANDOFF_URL = @json(route('admin.pickup.handoff'));

    let order = null;
    let tab = 'phone';
    let onDone = null;
    let handoff = null;
    let poll = null;
    let countdown = null;
    let photo = null;
    let busy = false;

    function token() {
        return document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    }

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function attr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function peso(amount) {
        if (amount === null || amount === undefined || amount === '' || !isFinite(Number(amount))) return '—';
        return '₱' + Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function notify(message, tone) {
        if (typeof window.showToast === 'function') window.showToast(message, tone);
        else if (tone === 'error') alert(message);
    }

    // ── Opening and closing ──────────────────────────────────────────────

    window.openPickup = function (openedOrder, doneHandler) {
        order = openedOrder;
        onDone = typeof doneHandler === 'function' ? doneHandler : null;
        handoff = null;
        photo = null;
        tab = 'phone';

        document.getElementById('pickupModal').style.display = 'flex';
        document.getElementById('pickupTabs').style.display = 'flex';
        document.getElementById('pickupSubtitle').textContent =
            `${order.receipt_no || ('Order #' + order.transaction_id)} · ${order.buyer_name || order.buyer_email || 'buyer'}`;

        render();
    };

    window.closePickup = function () {
        document.getElementById('pickupModal').style.display = 'none';
        stopWatching();
        order = null;
        handoff = null;
    };

    window.pickupTab = function (next) {
        if (tab === next) return;
        tab = next;
        stopWatching();
        render();
    };

    function stopWatching() {
        if (poll) { clearInterval(poll); poll = null; }
        if (countdown) { clearInterval(countdown); countdown = null; }
    }

    // ── The panel ────────────────────────────────────────────────────────

    function render() {
        for (const [name, id] of [['phone', 'pickupTabPhone'], ['here', 'pickupTabHere']]) {
            document.getElementById(id).className = 'fm-btn sm ' + (tab === name ? 'primary' : 'ghost');
        }

        const summary = `
            <div style="border: 1px solid var(--line, #e5e7eb); border-radius: 10px; padding: 12px; margin-bottom: 14px; background: var(--surface-sunk, #f9fafb); font-size: 13px;">
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 3px 0;">
                    <span style="color: var(--ink-500, #6b7280);">Item</span>
                    <span style="font-weight: 600;">${esc(order.item?.title || ('#' + order.item_id))}</span>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 3px 0;">
                    <span style="color: var(--ink-500, #6b7280);">Amount due</span>
                    <span style="font-weight: 600;">${peso(order.amount_due)}</span>
                </div>
                <div style="display: flex; justify-content: space-between; gap: 12px; padding: 3px 0;">
                    <span style="color: var(--ink-500, #6b7280);">Payment</span>
                    <span style="font-weight: 600;">${esc(order.payment_method || '')} · ${esc(order.payment_status || '')}</span>
                </div>
            </div>`;

        document.getElementById('pickupBody').innerHTML = summary + (tab === 'phone' ? phoneHtml() : hereHtml());

        if (tab === 'phone') startHandoff();
        else bindHere();
    }

    function phoneHtml() {
        return `
            <p style="font-size: 13px; color: var(--ink-700, #374151); margin: 0 0 12px;">
                Scan this with the phone at the counter. It opens this order's completion screen, where the
                handover photo is taken with the phone's camera.
            </p>
            <div id="pickupQr" style="display: flex; align-items: center; justify-content: center; min-height: 236px; padding: 12px; border: 1px solid var(--line, #e5e7eb); border-radius: 12px;">
                <span class="loading-spinner"></span>
            </div>
            <p id="pickupQrNote" style="font-size: 12px; color: var(--ink-500, #6b7280); margin: 10px 0 0; text-align: center;"></p>
            <div id="pickupLink" style="margin-top: 10px;"></div>
            <div id="pickupWatch" style="margin-top: 14px; font-size: 13px; color: var(--ink-600, #4b5563); display: flex; align-items: center; gap: 8px;">
                <span class="loading-spinner"></span><span>Waiting for the phone to finish…</span>
            </div>`;
    }

    async function startHandoff() {
        const host = document.getElementById('pickupQr');

        if (handoff) { drawHandoff(handoff); return; }

        try {
            const response = await fetch(PU_HANDOFF_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ transaction_id: order.transaction_id }),
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            handoff = payload;
            drawHandoff(payload);
        } catch (error) {
            host.innerHTML = `<p style="font-size: 13px; color: var(--danger, #b91c1c); margin: 0;">
                Could not create the counter link: ${esc(error.message)}</p>`;
            document.getElementById('pickupWatch').style.display = 'none';
        }
    }

    function drawHandoff(data) {
        const host = document.getElementById('pickupQr');
        host.innerHTML = '';

        if (typeof window.QRCode === 'function') {
            new window.QRCode(host, {
                text: data.url,
                width: 220,
                height: 220,
                correctLevel: window.QRCode.CorrectLevel.M,
            });
        } else {
            host.innerHTML = `<p style="font-size: 12.5px; color: var(--ink-500, #6b7280); margin: 0; text-align: center;">
                The QR drawing library did not load. Open the link below on the phone instead.</p>`;
        }

        document.getElementById('pickupLink').innerHTML = `
            <div style="display: flex; gap: 6px;">
                <input class="fm-input" id="pickupUrl" readonly value="${attr(data.url)}" style="font-size: 12px;">
                <button class="fm-btn ghost sm" onclick="pickupCopy()">Copy</button>
            </div>`;

        const left = data.expires_at
            ? Math.round((new Date(data.expires_at).getTime() - Date.now()) / 1000)
            : (data.expires_in || 1800);

        startCountdown(Math.max(0, left));
        watchForPhone();
    }

    window.pickupCopy = function () {
        const field = document.getElementById('pickupUrl');
        if (!field) return;
        field.select();
        if (navigator.clipboard?.writeText) navigator.clipboard.writeText(field.value).then(() => notify('Link copied', 'success'));
        else document.execCommand('copy');
    };

    function startCountdown(seconds) {
        const note = document.getElementById('pickupQrNote');
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
        countdown = setInterval(tick, 1000);
    }

    /** The phone finishing shows up on the order itself, so the order is re-read. */
    function watchForPhone() {
        poll = setInterval(async () => {
            try {
                const response = await fetch(`${PU_API}/admin/transactions/${order.transaction_id}`, {
                    headers: { 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json' },
                });
                if (!response.ok) return;

                const payload = await response.json().catch(() => ({}));
                const fresh = payload.data || {};

                if (fresh.status !== 'completed') return;

                stopWatching();
                finish(fresh);
            } catch (error) {
                // A dropped request is not an answer; the next tick asks again.
            }
        }, 4000);
    }

    // ── Do it here ───────────────────────────────────────────────────────

    function hereHtml() {
        return `
            <label class="fm-label">Handover photo</label>
            <p style="font-size: 12px; color: var(--ink-500, #6b7280); margin: -2px 0 6px;">
                The buyer receiving the item. This is the proof it changed hands.</p>
            <img id="pickupPreview" alt="" style="display: none; width: 100%; height: 170px; object-fit: cover; border-radius: 10px; margin-bottom: 6px;">
            <input id="pickupPhoto" class="fm-input" type="file" accept="image/*" capture="environment" style="margin-bottom: 14px;">

            <button class="fm-btn primary" id="pickupSubmit" style="width: 100%;" onclick="pickupComplete()">
                <i class="fas fa-handshake"></i>Complete handover
            </button>
            <p style="font-size: 12px; color: var(--ink-500, #6b7280); margin: 10px 0 0;">
                Completing credits the buyer's reward points, once.</p>`;
    }

    function bindHere() {
        document.getElementById('pickupPhoto').addEventListener('change', async (event) => {
            const file = event.target.files && event.target.files[0];
            if (!file) { photo = null; return; }

            const shrunk = await shrink(file);
            photo = shrunk.blob;

            const preview = document.getElementById('pickupPreview');
            if (shrunk.url) { preview.src = shrunk.url; preview.style.display = 'block'; }
        });
    }

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

    window.pickupComplete = async function () {
        if (busy || !order) return;

        const button = document.getElementById('pickupSubmit');
        busy = true;
        button.disabled = true;
        button.textContent = 'Completing…';

        const headers = { 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json' };
        let body;

        if (photo) {
            body = new FormData();
            body.append('handover_photo', photo, 'handover.jpg');
        } else {
            headers['Content-Type'] = 'application/json';
            body = '{}';
        }

        try {
            const response = await fetch(`${PU_API}/admin/transactions/${order.transaction_id}/complete`, {
                method: 'POST',
                headers,
                body,
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            busy = false;
            finish(payload.data || order);
        } catch (error) {
            busy = false;
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-handshake"></i>Complete handover';
            notify(`Could not complete: ${error.message}`, 'error');
        }
    };

    // ── Done, either way ─────────────────────────────────────────────────

    function finish(completed) {
        stopWatching();

        const earned = Number(completed.reward_points_to_credit || 0);

        document.getElementById('pickupTabs').style.display = 'none';
        document.getElementById('pickupBody').innerHTML = `
            <div style="text-align: center; padding: 10px 0 4px;">
                <i class="fas fa-circle-check" style="font-size: 38px; color: var(--success, #166534);"></i>
                <h4 style="margin: 12px 0 4px; font-size: 16px; font-weight: 700;">Order completed</h4>
                <p style="margin: 0; font-size: 13px; color: var(--ink-600, #4b5563);">
                    ${earned > 0
                        ? 'The buyer just earned ' + earned + ' reward point(s). They are told in the chat.'
                        : 'The item has been handed over.'}</p>
            </div>
            <button class="fm-btn primary" style="width: 100%; margin-top: 16px;" onclick="closePickup()">Done</button>`;

        notify('Order completed', 'success');

        if (onDone) onDone(completed);
    }
})();
</script>
@endpush
