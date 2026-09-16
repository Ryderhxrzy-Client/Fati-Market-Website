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
                <p class="cell-sub" style="margin-top: 2px;">Hold the phone's QR up to the camera, or type the receipt number from the buyer's order.</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <select id="cameraSelect" class="fm-input" style="width: auto; min-width: 150px; display: none;" onchange="switchCamera(this.value)" title="Camera"></select>
                <button class="fm-btn ghost sm" id="cameraToggle" onclick="toggleCamera()"><i class="fas fa-camera"></i>Start camera</button>
            </div>
        </div>
        <div style="position: relative; background: #0b0f0d; aspect-ratio: 4 / 3;">
            <video id="camVideo" playsinline muted style="width: 100%; height: 100%; object-fit: cover; display: block;"></video>
            <canvas id="camCanvas" hidden></canvas>
            <div id="camHint" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.75); font-size: 13px; text-align: center; padding: 24px;">
                <span><i class="fas fa-qrcode" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>Start the camera and show the phone's QR to it, type the code below, or read a photo of the QR.</span>
            </div>
            <div style="position: absolute; left: 50%; top: 50%; width: 56%; aspect-ratio: 1; transform: translate(-50%, -50%); border: 2px solid rgba(255,255,255,0.65); border-radius: 14px; pointer-events: none;"></div>
        </div>
        <div class="fm-card-body">
            <form onsubmit="lookupTyped(event)" class="flex gap-2">
                <input id="codeInput" class="fm-input" placeholder="Or type the receipt number (FM-000015), the order number, or the QR text" autocomplete="off">
                <button type="submit" class="fm-btn primary"><i class="fas fa-magnifying-glass"></i>Look up</button>
            </form>
            <p id="camStatus" class="cell-sub" style="margin-top: 8px;"></p>
            <label class="fm-btn ghost sm" style="margin-top: 10px; cursor: pointer;">
                <i class="fas fa-image"></i>Read a photo or screenshot of the QR
                <input id="qrImage" type="file" accept="image/*" hidden onchange="readQrImage(this.files[0])">
            </label>
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
    let detector = null;
    let scanTimer = null;

    const video = document.getElementById('camVideo');
    const canvas = document.getElementById('camCanvas');
    const CAMERA_KEY = 'fm_counter_camera';

    // Chrome's native reader when it exists (it copes with a phone screen far
    // better than a JS decoder); jsQR otherwise.
    if ('BarcodeDetector' in window) {
        try { detector = new BarcodeDetector({ formats: ['qr_code'] }); } catch (e) { detector = null; }
    }

    function cameraBlockedReason() {
        if (!window.isSecureContext) {
            return 'The browser only opens the camera on https or localhost. Open this page as http://localhost or over https, '
                + 'or type the code below.';
        }
        if (!navigator.mediaDevices?.getUserMedia) {
            return 'This browser cannot open the camera. Type the code below or read a photo of the QR.';
        }
        return null;
    }

    async function listCameras() {
        const select = document.getElementById('cameraSelect');
        try {
            const devices = (await navigator.mediaDevices.enumerateDevices()).filter(d => d.kind === 'videoinput');
            if (devices.length < 2) { select.style.display = 'none'; return; }
            const chosen = localStorage.getItem(CAMERA_KEY) || '';
            select.innerHTML = devices.map((d, i) => `<option value="${d.deviceId}" ${d.deviceId === chosen ? 'selected' : ''}>${d.label || 'Camera ' + (i + 1)}</option>`).join('');
            select.style.display = 'block';
        } catch (e) {
            select.style.display = 'none';
        }
    }

    async function openStream(deviceId) {
        // A phone: prefer the back camera. A laptop: whatever it has - the
        // "environment" wish is only a preference, never a requirement.
        const attempts = [];
        if (deviceId) attempts.push({ video: { deviceId: { exact: deviceId }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
        attempts.push({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false });
        attempts.push({ video: true, audio: false });

        let lastError = null;
        for (const constraints of attempts) {
            try {
                return await navigator.mediaDevices.getUserMedia(constraints);
            } catch (error) {
                lastError = error;
                if (error.name === 'NotAllowedError' || error.name === 'SecurityError') break;
            }
        }
        throw lastError || new Error('No camera');
    }

    async function toggleCamera() {
        if (stream) { stopCamera(); return; }

        const blocked = cameraBlockedReason();
        if (blocked) { setStatus(blocked, true); return; }

        try {
            stream = await openStream(localStorage.getItem(CAMERA_KEY) || '');
            video.srcObject = stream;
            await video.play();
            document.getElementById('camHint').style.display = 'none';
            document.getElementById('cameraToggle').innerHTML = '<i class="fas fa-stop"></i>Stop camera';
            scanning = true;
            setStatus(`Looking for a QR code… (${detector ? 'native reader' : 'jsQR'}). Hold the phone still, 15 to 25 cm from the camera, screen at full brightness.`);
            listCameras();
            scanTimer = setInterval(scanFrame, 120);
        } catch (error) {
            const why = error.name === 'NotAllowedError' ? 'Camera permission was denied. Allow it in the browser\'s site settings and try again.'
                : error.name === 'NotFoundError' ? 'No camera was found on this device.'
                : error.name === 'NotReadableError' ? 'The camera is in use by another app (Zoom, Teams, the emulator). Close it and try again.'
                : `Camera unavailable: ${error.message}`;
            setStatus(why + ' You can type the code or read a photo of the QR instead.', true);
        }
    }

    function switchCamera(deviceId) {
        localStorage.setItem(CAMERA_KEY, deviceId);
        if (stream) { stopCamera(); toggleCamera(); }
    }

    function stopCamera() {
        scanning = false;
        if (scanTimer) clearInterval(scanTimer);
        scanTimer = null;
        if (stream) stream.getTracks().forEach(t => t.stop());
        stream = null;
        video.srcObject = null;
        document.getElementById('camHint').style.display = 'flex';
        document.getElementById('cameraToggle').innerHTML = '<i class="fas fa-camera"></i>Start camera';
        setStatus('');
    }

    function drawFrame() {
        if (video.readyState !== video.HAVE_ENOUGH_DATA || !video.videoWidth) return null;
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const context = canvas.getContext('2d', { willReadFrequently: true });
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        return context;
    }

    /** Decode whatever is on the canvas: the native reader first, then jsQR on the full frame and on the centre. */
    async function decodeCanvas() {
        if (detector) {
            try {
                const codes = await detector.detect(canvas);
                if (codes.length && codes[0].rawValue) return codes[0].rawValue;
            } catch (e) { /* fall through to jsQR */ }
        }
        if (typeof jsQR !== 'function') return null;
        const context = canvas.getContext('2d', { willReadFrequently: true });
        const full = context.getImageData(0, 0, canvas.width, canvas.height);
        const hit = jsQR(full.data, full.width, full.height, { inversionAttempts: 'attemptBoth' });
        if (hit?.data) return hit.data;

        // A phone held near a webcam fills only the middle: try that region alone.
        const side = Math.floor(Math.min(canvas.width, canvas.height) * 0.7);
        const x = Math.floor((canvas.width - side) / 2), y = Math.floor((canvas.height - side) / 2);
        const centre = context.getImageData(x, y, side, side);
        const hit2 = jsQR(centre.data, centre.width, centre.height, { inversionAttempts: 'attemptBoth' });
        return hit2?.data || null;
    }

    let decoding = false;

    async function scanFrame() {
        if (!scanning || decoding) return;
        if (!drawFrame()) return;
        decoding = true;
        try {
            const code = await decodeCanvas();
            if (code) {
                const now = Date.now();
                // The same code stays in frame for a while; look it up once.
                if (code !== lastCode || now - lastAt > 8000) {
                    lastCode = code;
                    lastAt = now;
                    handleCode(code);
                }
            }
        } finally {
            decoding = false;
        }
    }

    /** The no-camera path: a photo or screenshot of the QR, decoded the same way. */
    async function readQrImage(file) {
        if (!file) return;
        setStatus('Reading the image…');
        try {
            const bitmap = await createImageBitmap(file);
            const scale = Math.min(1, 1600 / Math.max(bitmap.width, bitmap.height));
            canvas.width = Math.round(bitmap.width * scale);
            canvas.height = Math.round(bitmap.height * scale);
            canvas.getContext('2d', { willReadFrequently: true }).drawImage(bitmap, 0, 0, canvas.width, canvas.height);
            const code = await decodeCanvas();
            if (!code) throw new Error('No QR code was found in that image. Crop closer to the code and try again.');
            handleCode(code);
        } catch (error) {
            setStatus(error.message, true);
        } finally {
            document.getElementById('qrImage').value = '';
        }
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
