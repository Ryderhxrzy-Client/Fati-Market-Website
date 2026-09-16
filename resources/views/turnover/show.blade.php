{{--
    The item turnover screen, on whichever phone scanned the counter QR.

    It is deliberately not part of the admin console: the phone has never
    signed in here, and the only thing it carries is the one-item key from the
    QR. Everything it can do - receive this item, pay this seller, price it -
    goes back through this server, which still holds the admin's API token.

    The two photographs are what the whole page exists for, so they are taken
    with the phone's own camera and shrunk before they are sent, because the
    counter is not always where the signal is.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title>Item turnover · Fati Market</title>

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
        }

        .pill.brand { background: var(--brand-100); color: var(--brand-700); }

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

        label.field { display: block; font-size: 12.5px; font-weight: 600; color: var(--ink-700); margin: 0 0 6px; }

        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--ink-300);
            border-radius: 10px;
            font-size: 16px; /* keeps iOS from zooming the page on focus */
            font-family: inherit;
            background: white;
            color: var(--ink-900);
        }

        .proof { margin-bottom: 12px; }

        .proof img {
            width: 100%;
            height: 180px;
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

        .spacer { height: 10px; }
        .muted-note { font-size: 12px; color: var(--ink-500); text-align: center; margin: 12px 0 0; }
    </style>
</head>
<body>
<header>
    <h1><i class="fas fa-box-open"></i> Item turnover</h1>
    <p>Fati Market counter{{ $admin ? ' · ' . $admin : '' }}</p>
</header>

<main>
    @if ($loadError)
        <div class="banner danger">
            <b>The item could not be loaded</b>
            {{ $loadError }}
        </div>
    @else
        @php
            $status = strtolower($item['status'] ?? '');
            $received = !empty($item['is_turnover_verified']) || in_array($status, ['acquired', 'public', 'reserved', 'sold'], true);
            $photos = $item['photos'] ?? [];
            $agreed = $item['acquisition_price'] ?? null;
        @endphp

        <div class="card">
            @if (!empty($photos[0]))
                <img class="item-photo" src="{{ $photos[0] }}" alt="">
            @endif
            <p class="title">{{ $item['title'] ?? ('Item #' . ($item['item_id'] ?? '')) }}</p>
            <p class="sub">{{ $item['seller_email'] ?? 'Seller' }}</p>
            <div class="spacer"></div>
            <div class="row"><span>Asking price</span><span>{{ \App\Support\Peso::format($item['seller_asking_price'] ?? null) }}</span></div>
            <div class="row"><span>Agreed price</span><span>{{ \App\Support\Peso::format($agreed) }}</span></div>
            @if (!empty($item['public_price']))
                <div class="row"><span>Selling price</span><span>{{ \App\Support\Peso::format($item['public_price']) }}</span></div>
            @endif
            <div class="spacer"></div>
            <span class="pill brand">{{ ucfirst($status ?: 'pending') }}</span>
        </div>

        @if ($status === 'sold')
            <div class="banner danger"><b>This item has been sold</b>There is nothing left to receive.</div>
        @elseif ($status === 'rejected')
            <div class="banner danger"><b>This offer was declined</b>{{ $item['rejected_reason'] ?? 'There is nothing to receive.' }}</div>
        @else
            <form id="turnoverForm" autocomplete="off">
                <input type="hidden" name="k" value="{{ $key }}">

                @if ($received)
                    <div class="banner info">
                        <b>Already in the store</b>
                        This item was received already. Its selling price and its status can still be corrected here.
                    </div>
                @else
                    @if ($agreed === null)
                        <div class="card">
                            <p class="overline">Agreed price</p>
                            <p class="hint">No price was agreed in the conversation. Enter what the store is paying the seller.</p>
                            <label class="field" for="acquisitionPrice">What the store pays (₱)</label>
                            <input id="acquisitionPrice" name="acquisition_price" type="number" inputmode="decimal" step="0.01" min="0" placeholder="e.g. 180.00">
                        </div>
                    @endif

                    <div class="card">
                        <p class="overline">Turnover proof</p>
                        <p class="hint">Photograph the item you received, and the seller receiving their {{ \App\Support\Peso::format($agreed, 'cash') }}.</p>

                        <div class="proof">
                            <img id="itemPreview" alt="Item received">
                            <label class="capture" id="itemCapture">
                                <i class="fas fa-camera"></i><span>Take photo: item received</span>
                                <input type="file" accept="image/*" capture="environment" data-slot="item">
                            </label>
                        </div>

                        <div class="proof">
                            <img id="payoutPreview" alt="Seller paid">
                            <label class="capture" id="payoutCapture">
                                <i class="fas fa-camera"></i><span>Take photo: seller paid</span>
                                <input type="file" accept="image/*" capture="environment" data-slot="payout">
                            </label>
                        </div>
                    </div>
                @endif

                <div class="card">
                    <p class="overline">Selling price (optional)</p>
                    <p class="hint">Set the public price now to publish straight to the catalog, or leave it blank and price it later.</p>

                    <label class="field" for="publicPrice">Public selling price (₱)</label>
                    <input id="publicPrice" name="public_price" type="number" inputmode="decimal" step="0.01" min="0"
                           placeholder="e.g. 350.00" value="{{ $received ? ($item['public_price'] ?? '') : '' }}">

                    <p id="markupLine" class="hint" style="margin: 8px 0 0; display: none;"></p>

                    <div class="spacer"></div>

                    <label class="field" for="statusSelect">Status {{ $received ? '' : 'after turnover' }}</label>
                    <select id="statusSelect" name="status">
                        @foreach ($statuses as $option)
                            <option value="{{ $option }}" @selected($received && $status === $option)>{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                </div>

                @unless ($received)
                    <div class="card">
                        <p class="overline">Notes (optional)</p>
                        <label class="field" for="notes">Anything worth recording about this handover</label>
                        <input id="notes" name="notes" type="text" maxlength="500" placeholder="e.g. charger missing">
                    </div>
                @endunless

                <div id="formError" class="banner danger" style="display: none;"></div>

                <button type="submit" class="primary" id="submitButton">
                    {{ $received ? 'Save price & status' : 'Mark acquired & seller paid' }}
                </button>

                <p class="muted-note">This link works for this item only, and expires by itself.</p>
            </form>

            <div id="doneCard" style="display: none;">
                <div class="banner success"><b id="doneTitle">Item acquired</b><span id="doneText"></span></div>
                <div id="doneWarnings"></div>
                <p class="muted-note">You can close this page. The console has been updated.</p>
            </div>
        @endif
    @endif
</main>

<script>
    (function () {
        const form = document.getElementById('turnoverForm');
        if (!form) return;

        const received = @json($received ?? false);
        const agreed = @json($agreed ?? null);
        const postUrl = @json(route('turnover.complete', ['ref' => $ref]));

        const photos = { item: null, payout: null };
        let statusTouched = received;

        // ── The camera ───────────────────────────────────────────────────
        // A modern phone photograph is several megabytes and the counter is
        // not always where the signal is, so each one is drawn into a canvas
        // at a sane size before it is sent.
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

                    // An image the browser cannot decode is still sent as it
                    // came: the server, not this page, is the judge of it.
                    image.onerror = () => resolve({ blob: file, url: reader.result });
                    image.src = reader.result;
                };

                reader.onerror = () => resolve({ blob: file, url: null });
                reader.readAsDataURL(file);
            });
        }

        form.querySelectorAll('input[type="file"]').forEach((input) => {
            input.addEventListener('change', async () => {
                const file = input.files && input.files[0];
                if (!file) return;

                const slot = input.dataset.slot;
                const label = input.closest('.capture');
                label.querySelector('span').textContent = 'Preparing photo…';

                const shrunk = await shrink(file);
                photos[slot] = shrunk.blob;

                const preview = document.getElementById(slot + 'Preview');
                if (shrunk.url) {
                    preview.src = shrunk.url;
                    preview.style.display = 'block';
                }

                label.classList.add('done');
                label.querySelector('span').textContent =
                    slot === 'item' ? 'Retake: item received' : 'Retake: seller paid';

                refresh();
            });
        });

        // ── Price, markup, and where the item lands ──────────────────────
        const priceInput = document.getElementById('publicPrice');
        const statusSelect = document.getElementById('statusSelect');
        const markupLine = document.getElementById('markupLine');
        const button = document.getElementById('submitButton');

        statusSelect.addEventListener('change', () => { statusTouched = true; refresh(); });
        priceInput.addEventListener('input', refresh);

        function peso(value) {
            return '₱' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function typedPrice() {
            const raw = (priceInput.value || '').trim();
            if (!raw) return null;
            const value = Number(raw);
            return isFinite(value) && value > 0 ? value : null;
        }

        function refresh() {
            const price = typedPrice();

            // Left alone, the status follows the price: typing a selling
            // price publishes, which is what the field above promises.
            if (!statusTouched) statusSelect.value = price === null ? 'acquired' : 'public';

            const paid = Number(agreed ?? document.getElementById('acquisitionPrice')?.value ?? NaN);

            if (price !== null && isFinite(paid) && paid > 0) {
                markupLine.textContent = 'Markup ' + peso(price - paid) + ' over the ' + peso(paid) + ' paid to the seller.';
                markupLine.style.display = 'block';
            } else {
                markupLine.style.display = 'none';
            }

            const ready = received || (photos.item && photos.payout);
            const needsPrice = statusSelect.value === 'public' && price === null;

            button.disabled = !ready || needsPrice;
            button.textContent = needsPrice
                ? 'A published item needs a price'
                : received ? 'Save price & status'
                : !ready ? 'Take both photos first'
                : statusSelect.value === 'public' ? 'Mark acquired & publish'
                : statusSelect.value === 'acquired' ? 'Mark acquired & seller paid'
                : 'Mark acquired as ' + statusSelect.value;
        }

        // ── Sending it ───────────────────────────────────────────────────
        const errorBox = document.getElementById('formError');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            errorBox.style.display = 'none';
            button.disabled = true;
            button.textContent = 'Sending…';

            const body = new FormData();
            body.append('k', form.querySelector('input[name="k"]').value);

            const acquisition = document.getElementById('acquisitionPrice');
            if (acquisition && acquisition.value.trim()) body.append('acquisition_price', acquisition.value.trim());

            const notes = document.getElementById('notes');
            if (notes && notes.value.trim()) body.append('notes', notes.value.trim());

            if (priceInput.value.trim()) body.append('public_price', priceInput.value.trim());
            body.append('status', statusSelect.value);

            if (photos.item) body.append('turnover_photo', photos.item, 'item-received.jpg');
            if (photos.payout) body.append('payout_photo', photos.payout, 'seller-paid.jpg');

            try {
                const response = await fetch(postUrl, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) throw new Error(payload.message || ('The counter could not save this (HTTP ' + response.status + ').'));

                form.style.display = 'none';

                const done = document.getElementById('doneCard');
                const item = payload.item || {};
                const status = String(item.status || '').toLowerCase();

                document.getElementById('doneTitle').textContent =
                    payload.already_received ? 'Saved' : status === 'public' ? 'Acquired and published' : 'Item acquired';

                document.getElementById('doneText').textContent = payload.already_received
                    ? 'The item now reads as ' + (status || 'updated') + '.'
                    : 'The item is in the store and the seller has been paid'
                        + (status === 'public' ? ', and the listing is live in the catalog.' : '.');

                const warnings = payload.warnings || [];
                document.getElementById('doneWarnings').innerHTML = warnings
                    .map((line) => '<div class="banner warning">' + String(line).replace(/[<>&]/g, '') + '</div>')
                    .join('');

                done.style.display = 'block';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (error) {
                errorBox.textContent = error.message;
                errorBox.style.display = 'block';
                refresh();
            }
        });

        refresh();
    })();
</script>
</body>
</html>
