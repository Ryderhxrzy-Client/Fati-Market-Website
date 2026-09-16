{{--
    Handing an order over, on whichever phone scanned the counter QR.

    The twin of the turnover page. It has never signed in here; all it carries
    is the one-order key from the QR, and everything it can do goes back
    through this server, which still holds the admin's API token.

    The photograph is what the page exists for, so it is taken with the
    phone's own camera and shrunk before it is sent.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Complete handover · Fati Market</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-800: #10432D;
            --brand-700: #14563A;
            --brand-600: #1A6E49;
            --brand-100: #DCEFE4;
            --ink-900: #111827;
            --ink-700: #374151;
            --ink-500: #6B7280;
            --ink-300: #D1D5DB;
            --line: #E5E7EB;
            --surface: #FFFFFF;
            --sunk: #F3F4F6;
            --danger: #B91C1C;
            --danger-bg: #FEE2E2;
            --warning: #92400E;
            --warning-bg: #FEF3C7;
            --success: #166534;
            --success-bg: #DCFCE7;
            --info: #1D4ED8;
            --info-bg: #DBEAFE;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            padding: 0 0 env(safe-area-inset-bottom, 0px);
            background: #F7F8F7;
            color: var(--ink-900);
            font-family: Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            font-size: 15px;
            line-height: 1.45;
            -webkit-text-size-adjust: 100%;
        }

        header {
            background: var(--brand-800);
            color: white;
            padding: calc(14px + env(safe-area-inset-top, 0px)) 18px 14px;
        }

        header h1 { margin: 0; font-size: 17px; font-weight: 700; }
        header p { margin: 2px 0 0; font-size: 12.5px; color: rgba(255, 255, 255, 0.75); }

        main { padding: 14px 14px 40px; max-width: 560px; margin: 0 auto; }

        .card {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 14px;
        }

        .overline {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ink-500);
            margin: 0 0 8px;
        }

        .hint { font-size: 12.5px; color: var(--ink-500); margin: 0 0 10px; }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 13.5px;
            padding: 5px 0;
        }

        .row span:first-child { color: var(--ink-500); }
        .row span:last-child { font-weight: 600; text-align: right; }

        .item-photo {
            width: 100%;
            height: 170px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 10px;
            background: var(--sunk);
        }

        .title { margin: 0; font-size: 16px; font-weight: 700; }
        .sub { margin: 2px 0 0; font-size: 12.5px; color: var(--ink-500); }

        .pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11.5px;
            font-weight: 700;
            background: var(--sunk);
            color: var(--ink-700);
            margin-right: 6px;
        }

        .pill.brand { background: var(--brand-100); color: var(--brand-700); }
        .pill.warning { background: var(--warning-bg); color: var(--warning); }
        .pill.success { background: var(--success-bg); color: var(--success); }

        .banner {
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 14px;
        }

        .banner b { display: block; margin-bottom: 2px; font-size: 13.5px; }
        .banner.danger { background: var(--danger-bg); color: var(--danger); }
        .banner.warning { background: var(--warning-bg); color: var(--warning); }
        .banner.success { background: var(--success-bg); color: var(--success); }
        .banner.info { background: var(--info-bg); color: var(--info); }

        .proof img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 8px;
            display: none;
        }

        .capture {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px;
            border: 1.5px dashed var(--brand-600);
            border-radius: 10px;
            background: white;
            color: var(--brand-700);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .capture.done { border-style: solid; background: var(--brand-100); }
        .capture input { display: none; }

        button.primary {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 12px;
            background: var(--brand-600);
            color: white;
            font-size: 15.5px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
        }

        button.primary:disabled { background: #9CA3AF; cursor: not-allowed; }

        .muted-note { font-size: 12px; color: var(--ink-500); text-align: center; margin: 12px 0 0; }
    </style>
</head>
<body>
<header>
    <h1><i class="fas fa-handshake"></i> Complete handover</h1>
    <p>Fati Market counter{{ $admin ? ' · ' . $admin : '' }}</p>
</header>

<main>
    @if ($loadError)
        <div class="banner danger">
            <b>The order could not be loaded</b>
            {{ $loadError }}
        </div>
    @else
        @php
            $status = strtolower($order['status'] ?? '');
            $paymentStatus = strtolower($order['payment_status'] ?? '');
            $method = strtolower($order['payment_method'] ?? '');
            $photo = $order['item']['photos'][0] ?? null;
            $done = $status === 'completed';

            // Cash settles at this counter, so it is not asked for in
            // advance; anything else has to have landed already.
            $payable = $paymentStatus === 'verified' || ($method === 'cash' && $paymentStatus !== 'rejected');
            $terminal = in_array($status, ['cancelled', 'rejected'], true);
        @endphp

        <div class="card">
            @if ($photo)
                <img class="item-photo" src="{{ $photo }}" alt="">
            @endif
            <p class="title">{{ $order['item']['title'] ?? ('Order #' . ($order['transaction_id'] ?? '')) }}</p>
            <p class="sub">{{ $order['receipt_no'] ?? '' }}</p>
            <div style="height: 10px;"></div>
            <div class="row"><span>Buyer</span><span>{{ $order['buyer_name'] ?? $order['buyer_email'] ?? 'Buyer' }}</span></div>
            <div class="row"><span>Amount due</span><span>{{ \App\Support\Peso::format($order['amount_due'] ?? null) }}</span></div>
            @if (($order['points_used'] ?? 0) > 0)
                <div class="row"><span>{{ $order['points_used'] }} point(s) used</span><span>-{{ \App\Support\Peso::format($order['points_discount_amount'] ?? null) }}</span></div>
            @endif
            <div class="row"><span>Payment</span><span>{{ ucfirst($method ?: 'unknown') }}</span></div>
            <div style="height: 10px;"></div>
            <span class="pill brand">{{ ucfirst(str_replace('_', ' ', $status ?: 'unknown')) }}</span>
            <span class="pill {{ $paymentStatus === 'verified' ? 'success' : 'warning' }}">{{ ucfirst(str_replace('_', ' ', $paymentStatus ?: 'unpaid')) }}</span>
        </div>

        @if ($done)
            <div class="banner success"><b>Already completed</b>This order has been handed over.</div>
        @elseif ($terminal)
            <div class="banner danger"><b>This order is closed</b>It was cancelled or rejected, so there is nothing to hand over.</div>
        @else
            @if ($method === 'cash' && $paymentStatus !== 'verified')
                <div class="banner info">
                    <b>Take the cash</b>
                    Collect {{ \App\Support\Peso::format($order['amount_due'] ?? null) }} from the buyer. Completing this records it as paid.
                </div>
            @elseif (!$payable)
                <div class="banner warning">
                    <b>Payment first</b>
                    This order is not paid yet. Approve the payment on the console before handing the item over.
                </div>
            @endif

            <form id="pickupForm" autocomplete="off">
                <input type="hidden" name="k" value="{{ $key }}">

                <div class="card">
                    <p class="overline">Handover photo</p>
                    <p class="hint">Photograph the buyer receiving the item. This is the proof it really changed hands.</p>

                    <div class="proof">
                        <img id="handoverPreview" alt="Handover">
                        <label class="capture" id="handoverCapture">
                            <i class="fas fa-camera"></i><span>Take photo: the buyer receiving it</span>
                            <input type="file" accept="image/*" capture="environment" id="handoverPhoto">
                        </label>
                    </div>
                </div>

                <div id="formError" class="banner danger" style="display: none;"></div>

                <button type="submit" class="primary" id="submitButton" disabled>
                    {{ $method === 'cash' && $paymentStatus !== 'verified' ? 'Take payment & complete' : 'Complete handover' }}
                </button>

                <p class="muted-note">Completing credits the buyer's reward points, once. This link works for this order only.</p>
            </form>

            <div id="doneCard" style="display: none;">
                <div class="banner success"><b>Order completed</b><span id="doneText"></span></div>
                <p class="muted-note">You can close this page. The console has been updated.</p>
            </div>
        @endif
    @endif
</main>

<script>
    (function () {
        const form = document.getElementById('pickupForm');
        if (!form) return;

        const postUrl = @json(route('pickup.complete', ['ref' => $ref]));
        const button = document.getElementById('submitButton');
        const errorBox = document.getElementById('formError');
        let photo = null;

        // A phone photograph is several megabytes and the counter is not
        // always where the signal is.
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

                    image.onerror = () => resolve({ blob: file, url: reader.result });
                    image.src = reader.result;
                };

                reader.onerror = () => resolve({ blob: file, url: null });
                reader.readAsDataURL(file);
            });
        }

        document.getElementById('handoverPhoto').addEventListener('change', async (event) => {
            const file = event.target.files && event.target.files[0];
            if (!file) return;

            const label = document.getElementById('handoverCapture');
            label.querySelector('span').textContent = 'Preparing photo…';

            const shrunk = await shrink(file);
            photo = shrunk.blob;

            const preview = document.getElementById('handoverPreview');
            if (shrunk.url) {
                preview.src = shrunk.url;
                preview.style.display = 'block';
            }

            label.classList.add('done');
            label.querySelector('span').textContent = 'Retake the handover photo';
            button.disabled = false;
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!photo) return;

            errorBox.style.display = 'none';
            button.disabled = true;
            const label = button.textContent;
            button.textContent = 'Completing…';

            const body = new FormData();
            body.append('k', form.querySelector('input[name="k"]').value);
            body.append('handover_photo', photo, 'handover.jpg');

            try {
                const response = await fetch(postUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) throw new Error(payload.message || ('The counter could not complete this (HTTP ' + response.status + ').'));

                const order = payload.order || {};
                const earned = Number(order.reward_points_to_credit || 0);

                document.getElementById('doneText').textContent = earned > 0
                    ? 'The buyer just earned ' + earned + ' reward point(s). They are told in the chat.'
                    : 'The item has been handed over.';

                form.style.display = 'none';
                document.getElementById('doneCard').style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (error) {
                errorBox.textContent = error.message;
                errorBox.style.display = 'block';
                button.disabled = false;
                button.textContent = label;
            }
        });
    })();
</script>
</body>
</html>
