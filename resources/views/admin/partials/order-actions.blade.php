{{--
    An order, opened for a decision.

    The transactions page and the counter both need the same thing the mobile
    admin app gives an order: its summary, the buyer's receipt, and whichever
    of the server's `available_actions` are still open - approve, decline,
    ready for pickup, complete (with the handover photo), cancel. Include once
    per page, then call FMOrders.open(order, onChange).
--}}

<div id="orderModal"
     onclick="if (event.target === this) FMOrders.close()"
     style="display: none; position: fixed; inset: 0; background: rgba(12, 48, 33, 0.55); z-index: 70; align-items: center; justify-content: center; padding: 24px;">
    <div id="orderModalBody" style="background: white; border-radius: 12px; max-width: 560px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: var(--shadow-lg);"></div>
</div>

<div id="orderAskModal"
     onclick="if (event.target === this) FMOrders.askDone(false)"
     style="display: none; position: fixed; inset: 0; background: rgba(12, 48, 33, 0.55); z-index: 80; align-items: center; justify-content: center; padding: 24px;">
    <div id="orderAskBody" style="background: white; border-radius: 12px; max-width: 420px; width: 100%; padding: 20px; box-shadow: var(--shadow-lg);"></div>
</div>

@push('scripts')
<script>
window.FMOrders = (function () {
    const API = 'https://fati-api.alertaraqc.com/api';
    const PESO = '₱';

    let current = null;
    let onChange = null;
    let busy = false;
    let askResolve = null;

    function token() {
        return document.querySelector('meta[name="api-token"]')?.getAttribute('content')
            || sessionStorage.getItem('admin_token')
            || localStorage.getItem('admin_token')
            || '';
    }

    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function attr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;')
            .replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function peso(amount) {
        const value = Number(amount);
        if (!isFinite(value)) return PESO + '0.00';
        return PESO + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function methodLabel(method) {
        return { gcash: 'GCash', points_full: 'Points only', cash: 'Cash at store' }[method] || (method || 'Unknown');
    }

    function badge(label, tone) {
        return `<span class="fm-badge ${tone || ''}">${esc(label)}</span>`;
    }

    /** Whether the money has arrived - separate from where the order stands. */
    function paymentBadge(order) {
        const map = {
            verified: [order.is_full_points_checkout ? 'Paid with points' : 'Paid', 'success'],
            proof_submitted: ['Checking payment', 'warning'],
            rejected: ['Payment declined', 'danger'],
        };
        const unpaid = order.payment_method === 'cash'
            ? (order.status === 'pending_payment' ? ['Waiting for approval', 'warning'] : ['Pay on pickup', 'info'])
            : ['Not paid yet', 'warning'];
        const [label, tone] = map[order.payment_status] || unpaid;
        return badge(label, tone);
    }

    function statusBadge(order) {
        const awaiting = order.payment_method === 'cash'
            ? ['Awaiting admin approval', 'warning']
            : ['Awaiting payment', 'warning'];
        const map = {
            pending_payment: awaiting,
            payment_proof_submitted: ['Proof submitted', 'info'],
            payment_verified: ['Payment verified', 'info'],
            reserved: ['Reserved', 'info'],
            ready_for_pickup: ['Ready for pickup', 'brand'],
            completed: ['Completed', 'success'],
            cancelled: ['Cancelled', ''],
            rejected: ['Rejected', 'danger'],
        };
        const [label, tone] = map[order.status] || [order.status || 'Unknown', ''];
        return badge(label, tone);
    }

    function row(label, value, strong) {
        return `
            <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 4px 0;">
                <span style="color: var(--ink-500);">${esc(label)}</span>
                <span style="color: var(--ink-900); ${strong ? 'font-weight: 700;' : ''} text-align: right;">${esc(value)}</span>
            </div>`;
    }

    function when(value) {
        if (!value) return '—';
        const date = new Date(value);
        return isNaN(date.getTime()) ? String(value) : date.toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    /** The buttons for the decisions the server says are still open. */
    function actionsHtml(order) {
        const actions = order.available_actions || [];
        const buttons = [];
        const solid = 'fm-btn primary sm';
        const ghost = 'fm-btn ghost sm';
        const danger = 'fm-btn danger sm';

        if (actions.includes('verify_payment')) buttons.push(`<button class="${solid}" onclick="FMOrders.run('verify-payment', 'Approve payment')"><i class="fas fa-check"></i>Approve payment</button>`);
        if (actions.includes('approve_order')) buttons.push(`<button class="${solid}" onclick="FMOrders.run('approve-order', 'Approve order')"><i class="fas fa-check"></i>Approve order</button>`);
        if (actions.includes('mark_ready_for_pickup')) buttons.push(`<button class="${ghost}" onclick="FMOrders.run('ready-for-pickup', 'Ready for pickup')"><i class="fas fa-box-open"></i>Ready for pickup</button>`);
        if (actions.includes('complete')) buttons.push(`<button class="${solid}" onclick="FMOrders.run('complete', 'Complete handover')"><i class="fas fa-handshake"></i>Complete handover</button>`);
        if (actions.includes('reject_payment')) buttons.push(`<button class="${danger}" onclick="FMOrders.run('reject-payment', 'Decline payment')"><i class="fas fa-xmark"></i>Decline payment</button>`);
        else if (actions.includes('cancel')) buttons.push(`<button class="${danger}" onclick="FMOrders.run('cancel', 'Cancel order')"><i class="fas fa-ban"></i>Cancel order</button>`);

        if (!buttons.length) return '';

        return `
            <div style="border-top: 1px solid var(--line); padding: 14px 20px; display: flex; gap: 8px; flex-wrap: wrap; background: var(--surface-sunk);">
                ${buttons.join('')}
            </div>`;
    }

    function render() {
        const order = current;
        const item = order.item || {};
        const buyer = order.buyer || {};
        const photo = (item.photos && item.photos[0]) || null;
        const buyerName = buyer.name || (buyer.email ? buyer.email.split('@')[0] : 'Buyer');

        document.getElementById('orderModalBody').innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--line);">
                <div style="min-width: 0;">
                    <h4 style="margin: 0; font-size: 15px; font-weight: 700;">${esc(order.receipt_no || ('Order #' + order.transaction_id))}</h4>
                    <p class="cell-sub" style="margin-top: 2px;">Placed ${esc(when(order.created_at || order.transaction_date))}</p>
                </div>
                <button onclick="FMOrders.close()" aria-label="Close" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--ink-500);">&times;</button>
            </div>

            <div style="padding: 16px 20px;">
                <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px;">
                    ${statusBadge(order)}
                    ${paymentBadge(order)}
                    ${order.pickup_status === 'picked_up' ? badge('Picked up', 'success') : ''}
                </div>

                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 14px;">
                    ${photo
                        ? `<img src="${attr(photo)}" alt="" style="width: 64px; height: 64px; border-radius: 8px; object-fit: cover; flex-shrink: 0;">`
                        : `<div class="thumb" style="width: 64px; height: 64px;"><i class="fas fa-image"></i></div>`}
                    <div style="min-width: 0;">
                        <p class="cell-title" style="margin: 0;">${esc(item.title || ('Item #' + order.item_id))}</p>
                        <p class="cell-sub">Item #${esc(order.item_id)}</p>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: center; padding: 10px 12px; background: var(--surface-sunk); border-radius: 8px; margin-bottom: 14px;">
                    ${buyer.profile_picture
                        ? `<img src="${attr(buyer.profile_picture)}" alt="" class="avatar" style="width: 36px; height: 36px;">`
                        : `<div class="avatar" style="width: 36px; height: 36px;">${esc(buyerName.charAt(0).toUpperCase())}</div>`}
                    <div style="min-width: 0; flex: 1;">
                        <p class="cell-title" style="margin: 0;">${esc(buyerName)}</p>
                        <p class="cell-sub" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${esc(buyer.email || '')}</p>
                    </div>
                    ${buyer.wallet_points !== undefined ? badge(`${buyer.wallet_points} pts`, 'reward') : ''}
                </div>

                <div style="border-top: 1px solid var(--line); padding-top: 8px;">
                    ${row('Price', peso(order.subtotal))}
                    ${Number(order.points_used) > 0 ? row(`${order.points_used} point(s) used`, '-' + peso(order.points_discount_amount)) : ''}
                    ${row('Amount due', peso(order.amount_due), true)}
                    ${row('Payment', methodLabel(order.payment_method))}
                    ${order.payment_reference ? row('Reference', order.payment_reference) : ''}
                    ${order.reserved_until ? row('Reserved until', when(order.reserved_until)) : ''}
                    ${order.reward_points_to_credit ? row('Buyer earns', `${order.reward_points_to_credit} point(s)${order.reward_points_credited ? ' (credited)' : ''}`) : ''}
                    ${order.completed_at ? row('Completed', when(order.completed_at)) : ''}
                </div>

                ${order.cancel_reason ? `<p style="margin: 12px 0 0; padding: 8px 10px; background: var(--danger-bg); border-radius: 6px; font-size: 12.5px; color: var(--danger);">${esc(order.cancel_reason)}</p>` : ''}

                ${order.payment_proof ? `
                    <p class="fm-label" style="margin: 14px 0 6px;">Payment receipt</p>
                    <a href="${attr(order.payment_proof)}" target="_blank" rel="noopener">
                        <img src="${attr(order.payment_proof)}" alt="Payment receipt" style="width: 100%; max-height: 260px; object-fit: contain; border-radius: 8px; background: var(--surface-sunk); border: 1px solid var(--line);">
                    </a>
                    <p class="cell-sub" style="margin-top: 4px;">Open in a new tab to see it in full.</p>
                ` : ''}

                ${order.handover_photo ? `
                    <p class="fm-label" style="margin: 14px 0 6px;">Handover photo</p>
                    <a href="${attr(order.handover_photo)}" target="_blank" rel="noopener">
                        <img src="${attr(order.handover_photo)}" alt="Handover" style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 8px;">
                    </a>
                ` : ''}
            </div>

            ${actionsHtml(order)}
        `;

        document.getElementById('orderModal').style.display = 'flex';
    }

    /** The decision dialog: a reason, a photo, or just a confirmation. */
    function ask({ title, body, field, confirmLabel, danger }) {
        return new Promise((resolve) => {
            askResolve = resolve;

            let fieldHtml = '';
            if (field?.type === 'textarea') {
                fieldHtml = `<textarea id="orderAskInput" rows="3" class="fm-input" style="margin-top: 10px;" placeholder="${attr(field.placeholder || '')}"></textarea>`;
            } else if (field?.type === 'file') {
                fieldHtml = `<input id="orderAskInput" type="file" accept="image/*" capture="environment" class="fm-input" style="margin-top: 10px;">`;
            }

            document.getElementById('orderAskBody').innerHTML = `
                <h4 style="margin: 0; font-size: 16px; font-weight: 700;">${esc(title)}</h4>
                <p style="margin: 8px 0 0; font-size: 13px; color: var(--ink-600);">${esc(body || '')}</p>
                ${field?.label ? `<label class="fm-label" style="margin-top: 12px;">${esc(field.label)}</label>` : ''}
                ${fieldHtml}
                <div style="display: flex; justify-content: flex-end; gap: 8px; margin-top: 18px;">
                    <button class="fm-btn ghost" onclick="FMOrders.askDone(false)">Cancel</button>
                    <button class="fm-btn ${danger ? 'danger' : 'primary'}" onclick="FMOrders.askDone(true)">${esc(confirmLabel || 'Confirm')}</button>
                </div>`;

            document.getElementById('orderAskModal').style.display = 'flex';
            setTimeout(() => document.getElementById('orderAskInput')?.focus(), 60);
        });
    }

    function askDone(confirmed) {
        const input = document.getElementById('orderAskInput');
        let value = '';
        if (input) value = input.type === 'file' ? (input.files[0] || null) : input.value;

        document.getElementById('orderAskModal').style.display = 'none';
        const resolve = askResolve;
        askResolve = null;
        if (resolve) resolve(confirmed ? value : null);
    }

    async function run(endpoint, label) {
        if (!current || busy) return;

        const needsReason = endpoint === 'reject-payment' || endpoint === 'cancel';
        let body = null;
        let isForm = false;

        if (needsReason) {
            const reason = await ask({
                title: label,
                body: 'The buyer is told in their chat. Give them the reason.',
                field: { label: 'Reason', type: 'textarea', placeholder: 'Shown to the buyer' },
                confirmLabel: label,
                danger: true,
            });
            if (reason === null) return;
            if (!String(reason).trim()) { showToast('A reason is required.', 'error'); return; }
            body = JSON.stringify({ reason: String(reason).trim() });
        } else if (endpoint === 'complete') {
            const photo = await ask({
                title: 'Complete the handover?',
                body: 'This credits the buyer\'s reward points, once. Attach the photo taken at the counter if you have one.',
                field: { label: 'Handover photo (optional)', type: 'file' },
                confirmLabel: 'Complete',
            });
            if (photo === null) return;
            if (photo) {
                body = new FormData();
                body.append('handover_photo', photo, photo.name);
                isForm = true;
            } else {
                body = '{}';
            }
        } else {
            const confirmed = await ask({
                title: `${label}?`,
                body: endpoint === 'approve-order'
                    ? 'The item is held and the buyer gets their pickup code. It is not marked paid - the cash is taken when they collect it.'
                    : 'The buyer sees the result in their conversation right away.',
                confirmLabel: label,
            });
            if (confirmed === null) return;
            body = '{}';
        }

        busy = true;

        try {
            const headers = { 'Authorization': `Bearer ${token()}`, 'Accept': 'application/json' };
            if (!isForm) headers['Content-Type'] = 'application/json';

            const response = await fetch(`${API}/admin/transactions/${current.transaction_id}/${endpoint}`, {
                method: 'POST',
                headers,
                body,
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            current = payload.data || current;
            render();
            showToast(`${label}: done`, 'success');
            if (typeof onChange === 'function') onChange(current);
        } catch (error) {
            showToast(`Could not ${label.toLowerCase()}: ${error.message}`, 'error');
        } finally {
            busy = false;
        }
    }

    function open(order, changeHandler) {
        current = order;
        onChange = changeHandler || null;
        render();
    }

    function close() {
        document.getElementById('orderModal').style.display = 'none';
        current = null;
    }

    return { open, close, run, askDone, peso, methodLabel, paymentBadge, statusBadge, when, esc, attr };
})();
</script>
@endpush
