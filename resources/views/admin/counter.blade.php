@extends('layouts.admin-dashboard')

@section('title', 'Counter')
@section('subtitle', 'Scan a seller\'s turnover code or a buyer\'s pickup code')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
    {{--
        The Scan tab of the mobile app, for the desk. A seller's turnover QR
        (FMITEM1.…) opens the item's acquisition workflow; a buyer's pickup QR
        opens the order with its handover decision. The camera reads the code
        when the browser allows it; the code can also be typed or pasted.
    --}}
    <section class="fm-card">
        <div class="fm-card-head">
            <div>
                <h4>Scan a code</h4>
                <p class="cell-sub" style="margin-top: 2px;">Hold the phone's QR up to the camera.</p>
            </div>
            <button class="fm-btn ghost sm" id="cameraToggle" onclick="toggleCamera()"><i class="fas fa-camera"></i>Start camera</button>
        </div>
        <div style="position: relative; background: #0b0f0d; aspect-ratio: 4 / 3;">
            <video id="camVideo" playsinline muted style="width: 100%; height: 100%; object-fit: cover; display: block;"></video>
            <canvas id="camCanvas" hidden></canvas>
            <div id="camHint" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.75); font-size: 13px; text-align: center; padding: 24px;">
                <span><i class="fas fa-qrcode" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>Start the camera, or type the code below.</span>
            </div>
            <div style="position: absolute; left: 50%; top: 50%; width: 56%; aspect-ratio: 1; transform: translate(-50%, -50%); border: 2px solid rgba(255,255,255,0.65); border-radius: 14px; pointer-events: none;"></div>
        </div>
        <div class="fm-card-body">
            <form onsubmit="lookupTyped(event)" class="flex gap-2">
                <input id="codeInput" class="fm-input" placeholder="Or paste the code: FMITEM1.… or FMORD…" autocomplete="off">
                <button type="submit" class="fm-btn primary"><i class="fas fa-magnifying-glass"></i>Look up</button>
            </form>
            <p id="camStatus" class="cell-sub" style="margin-top: 8px;"></p>
        </div>
    </section>

    <section class="fm-card" id="resultCard">
        <div class="fm-card-head"><h4>Result</h4></div>
        <div id="resultBody" class="fm-card-body">
            <div class="fm-empty" style="padding: 40px 16px;">
                <i class="fas fa-hand-holding"></i>
                <p>Nothing scanned yet</p>
                <span>The item or order behind the code appears here, with what to do next.</span>
            </div>
        </div>
    </section>
</div>

@include('admin.partials.item-workflow')
@include('admin.partials.order-actions')

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jsQR/1.4.0/jsQR.min.js"></script>
<script>
    const API = 'https://fati-api.alertaraqc.com/api';
    const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    const headers = { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' };

    let stream = null;
    let scanning = false;
    let lastCode = null;
    let lastAt = 0;
    let busy = false;

    const video = document.getElementById('camVideo');
    const canvas = document.getElementById('camCanvas');

    async function toggleCamera() {
        if (stream) { stopCamera(); return; }

        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('This browser cannot open the camera. Type the code instead.', true);
            return;
        }

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            video.srcObject = stream;
            await video.play();
            document.getElementById('camHint').style.display = 'none';
            document.getElementById('cameraToggle').innerHTML = '<i class="fas fa-stop"></i>Stop camera';
            scanning = true;
            setStatus('Looking for a QR code…');
            requestAnimationFrame(scanFrame);
        } catch (error) {
            setStatus('Camera unavailable: ' + error.message + '. Type the code instead.', true);
        }
    }

    function stopCamera() {
        scanning = false;
        if (stream) stream.getTracks().forEach(t => t.stop());
        stream = null;
        video.srcObject = null;
        document.getElementById('camHint').style.display = 'flex';
        document.getElementById('cameraToggle').innerHTML = '<i class="fas fa-camera"></i>Start camera';
        setStatus('');
    }

    function scanFrame() {
        if (!scanning) return;

        if (video.readyState === video.HAVE_ENOUGH_DATA && typeof jsQR === 'function') {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d', { willReadFrequently: true });
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const image = context.getImageData(0, 0, canvas.width, canvas.height);
            const result = jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' });

            if (result && result.data) {
                const now = Date.now();
                // The same code stays in frame for a while; look it up once.
                if (result.data !== lastCode || now - lastAt > 8000) {
                    lastCode = result.data;
                    lastAt = now;
                    handleCode(result.data);
                }
            }
        }

        requestAnimationFrame(scanFrame);
    }

    function setStatus(text, isError) {
        const el = document.getElementById('camStatus');
        el.textContent = text;
        el.style.color = isError ? 'var(--danger)' : 'var(--ink-500)';
    }

    function lookupTyped(event) {
        event.preventDefault();
        const code = document.getElementById('codeInput').value.trim();
        if (code) handleCode(code);
    }

    async function handleCode(code) {
        if (busy) return;
        busy = true;
        document.getElementById('codeInput').value = code;
        showResult('<div style="padding: 24px; text-align: center;"><span class="loading-spinner"></span><p class="cell-sub" style="margin-top: 8px;">Looking up the code…</p></div>');

        try {
            if (code.startsWith('FMITEM1.')) {
                const response = await fetch(`${API}/admin/items/scan?code=${encodeURIComponent(code)}`, { headers });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
                showItem(payload.data || {});
            } else {
                const response = await fetch(`${API}/admin/transactions/scan?code=${encodeURIComponent(code)}`, { headers });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
                showOrder(payload.data || {});
            }
            setStatus('Code recognised.');
        } catch (error) {
            showResult(`
                <div class="fm-empty" style="padding: 32px 16px;">
                    <i class="fas fa-triangle-exclamation" style="color: var(--danger);"></i>
                    <p>Not a valid Fati Market code</p>
                    <span>${FMOrders.esc(error.message)}</span>
                </div>`);
            setStatus(error.message, true);
        } finally {
            busy = false;
        }
    }

    function showResult(html) {
        document.getElementById('resultBody').innerHTML = html;
    }

    function showItem(item) {
        const photo = (item.photos && item.photos[0]) || null;
        const paid = item.seller_payout_status === 'paid';

        showResult(`
            <div class="flex gap-2 flex-wrap mb-3">
                <span class="fm-badge brand"><i class="fas fa-tag"></i>Seller turnover</span>
                <span class="fm-badge">${FMOrders.esc(item.status || '')}</span>
                ${item.is_turnover_verified ? '<span class="fm-badge success">Received</span>' : '<span class="fm-badge warning">Not received yet</span>'}
                ${paid ? '<span class="fm-badge success">Seller paid</span>' : ''}
            </div>
            <div class="flex items-center gap-3 mb-4">
                ${photo ? `<img src="${FMOrders.attr(photo)}" alt="" style="width: 72px; height: 72px; border-radius: 8px; object-fit: cover;">` : '<div class="thumb" style="width: 72px; height: 72px;"><i class="fas fa-image"></i></div>'}
                <div class="min-w-0">
                    <p class="cell-title" style="font-size: 15px;">${FMOrders.esc(item.title || ('Item #' + item.item_id))}</p>
                    <p class="cell-sub">${FMOrders.esc(item.seller_email || '')} &middot; Item #${FMOrders.esc(item.item_id)}</p>
                </div>
            </div>
            <div style="border-top: 1px solid var(--line); padding-top: 8px; margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0;"><span style="color: var(--ink-500);">Seller asking</span><span>${FMOrders.peso(item.seller_asking_price)}</span></div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0;"><span style="color: var(--ink-500);">Acquisition price</span><strong>${item.acquisition_price ? FMOrders.peso(item.acquisition_price) : 'Not set'}</strong></div>
                ${item.meetup_schedule ? `<div style="display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0;"><span style="color: var(--ink-500);">Meet-up</span><span>${FMOrders.esc(FMOrders.when(item.meetup_schedule))}</span></div>` : ''}
            </div>
            <p style="font-size: 13px; color: var(--ink-600); margin-bottom: 12px;">
                ${item.is_turnover_verified
                    ? 'This item is already in the store. Open the workflow to record the payout or publish it.'
                    : 'Confirm the item is in hand and the seller was paid. The workflow takes the counter photos as proof.'}
            </p>
            <button class="fm-btn primary" onclick="openItemWorkflow(${Number(item.item_id)})"><i class="fas fa-diagram-project"></i>Open item workflow</button>
        `);
    }

    function showOrder(order) {
        const item = order.item || {};
        const buyer = order.buyer || {};
        const photo = (item.photos && item.photos[0]) || null;
        const canComplete = (order.available_actions || []).includes('complete');

        showResult(`
            <div class="flex gap-2 flex-wrap mb-3">
                <span class="fm-badge brand"><i class="fas fa-bag-shopping"></i>Buyer pickup</span>
                ${FMOrders.statusBadge(order)}
                ${FMOrders.paymentBadge(order)}
            </div>
            <div class="flex items-center gap-3 mb-3">
                ${photo ? `<img src="${FMOrders.attr(photo)}" alt="" style="width: 72px; height: 72px; border-radius: 8px; object-fit: cover;">` : '<div class="thumb" style="width: 72px; height: 72px;"><i class="fas fa-image"></i></div>'}
                <div class="min-w-0">
                    <p class="cell-title" style="font-size: 15px;">${FMOrders.esc(item.title || ('Item #' + order.item_id))}</p>
                    <p class="cell-sub">${FMOrders.esc(order.receipt_no || ('Order #' + order.transaction_id))}</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; padding: 10px 12px; background: var(--surface-sunk); border-radius: 8px; margin-bottom: 12px;">
                <div class="avatar" style="width: 32px; height: 32px;">${FMOrders.esc((buyer.name || buyer.email || 'B').charAt(0).toUpperCase())}</div>
                <div class="min-w-0">
                    <p class="cell-title" style="margin: 0;">${FMOrders.esc(buyer.name || (buyer.email || '').split('@')[0] || 'Buyer')}</p>
                    <p class="cell-sub">${FMOrders.esc(buyer.email || '')}</p>
                </div>
            </div>
            <div style="border-top: 1px solid var(--line); padding-top: 8px; margin-bottom: 14px;">
                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0;"><span style="color: var(--ink-500);">Amount due</span><strong>${FMOrders.peso(order.amount_due)}</strong></div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; padding: 4px 0;"><span style="color: var(--ink-500);">Payment</span><span>${FMOrders.esc(FMOrders.methodLabel(order.payment_method))}</span></div>
            </div>
            <p style="font-size: 13px; color: var(--ink-600); margin-bottom: 12px;">
                ${canComplete
                    ? (order.payment_method === 'cash' && order.payment_status !== 'verified'
                        ? 'Take the cash, hand over the item, then complete the order. The buyer\'s reward points are credited on completion.'
                        : 'Hand over the item and complete the order. The buyer\'s reward points are credited on completion.')
                    : order.status === 'completed'
                        ? 'This order is already completed.'
                        : 'This order cannot be completed yet - open it to see what is pending.'}
            </p>
            <button class="fm-btn ${canComplete ? 'primary' : 'ghost'}" onclick="openScannedOrder()">
                <i class="fas ${canComplete ? 'fa-handshake' : 'fa-eye'}"></i>${canComplete ? 'Complete handover' : 'Open order'}
            </button>
        `);

        window.scannedOrder = order;
    }

    function openScannedOrder() {
        if (!window.scannedOrder) return;
        FMOrders.open(window.scannedOrder, (updated) => showOrder(updated));
    }

    window.addEventListener('beforeunload', stopCamera);
</script>
@endpush
@endsection
