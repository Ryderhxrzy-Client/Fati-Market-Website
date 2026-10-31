@include('admin.partials.pickup-date-modal')
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
    let readOnly = false;
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
        return { gcash: 'GCash', points_full: 'Points only', cash: 'Cash on pickup (50% DP)', paymongo: 'PayMongo (Online)' }[method] || (method || 'Unknown');
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
            overdue: ['Overdue', 'danger'],
            completed: ['Completed', 'success'],
            cancelled: ['Cancelled', ''],
            rejected: ['Rejected', 'danger'],
        };
        const [label, tone] = map[order.status] || [order.status || 'Unknown', ''];
        return badge(label, tone);
    }

    function row(label, value, strong, isHtml) {
        return `
            <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 4px 0; align-items: center;">
                <span style="color: var(--ink-500);">${esc(label)}</span>
                <span style="color: var(--ink-900); ${strong ? 'font-weight: 700;' : ''} text-align: right;">${isHtml ? value : esc(value)}</span>
            </div>`;
    }

    function when(value) {
        if (!value) return '—';
        const date = new Date(value);
        return isNaN(date.getTime()) ? String(value) : date.toLocaleString([], { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    /** The buttons for the decisions the server says are still open. */
    function actionsHtml(order) {
        const actions = readOnly ? [] : (order.available_actions || []);
        const buttons = [];
        const solid = 'fm-btn primary sm';
        const ghost = 'fm-btn ghost sm';
        const danger = 'fm-btn danger sm';

        const isPaid = order.payment_status === 'verified'
            || order.payment_status === 'partially_paid'
            || !!order.has_paid_downpayment
            || !!order.has_paid
            || ['verified', 'paid'].includes(order.downpayment_status);

        const isAlreadyReady = order.status === 'ready_for_pickup'
            || order.pickup_status === 'ready';

        if (order.status === 'overdue' || actions.includes('extend_pickup_date')) {
            buttons.push(`<button class="${solid}" onclick="FMOrders.extendDate()"><i class="fas fa-calendar-plus" style="margin-right: 5px;"></i>Extend pick-up date</button>`);
        } else if (!['completed', 'cancelled', 'rejected'].includes(order.status)) {
            buttons.push(`<button class="${ghost}" onclick="FMOrders.extendDate()"><i class="fas fa-calendar-alt" style="margin-right: 5px;"></i>${order.pickup_date ? 'Edit pick-up date' : 'Set pick-up date'}</button>`);
        }

        if (order.status === 'overdue' || actions.includes('forfeit_and_relist') || actions.includes('cancel_and_relist')) {
            if (isPaid) {
                const hasRemainingBalance = Number(order.remaining_balance) > 0;
                buttons.push(`<button class="${danger}" onclick="FMOrders.forfeitRelist(false)"><i class="fas fa-ban" style="margin-right: 5px;"></i>${hasRemainingBalance ? 'Forfeit DP & Relist' : 'Forfeit Payment & Relist'}</button>`);
                buttons.push(`<button class="${solid}" style="background: #dc2626;" onclick="FMOrders.forfeitRelist(true)"><i class="fas fa-rotate-left" style="margin-right: 5px;"></i>Refund & Relist</button>`);
            } else {
                buttons.push(`<button class="${danger}" onclick="FMOrders.forfeitRelist(false)"><i class="fas fa-undo" style="margin-right: 5px;"></i>Cancel & Relist Item</button>`);
            }
        }

        if (order.status === 'overdue') {
            buttons.push(`<button class="${solid}" style="background: var(--success, #16a34a);" onclick="FMOrders.handover()"><i class="fas fa-handshake" style="margin-right: 5px;"></i>Mark as Sold</button>`);
        } else if (actions.includes('complete')) {
            buttons.push(`<button class="${solid}" onclick="FMOrders.handover()"><i class="fas fa-handshake"></i>Complete handover</button>`);
        }

        if (actions.includes('verify_payment')) buttons.push(`<button class="${solid}" onclick="FMOrders.run('verify-payment', 'Approve payment')"><i class="fas fa-check"></i>Approve payment</button>`);
        if (actions.includes('approve_order')) buttons.push(`<button class="${solid}" onclick="FMOrders.run('approve-order', 'Approve order')"><i class="fas fa-check"></i>Approve order</button>`);

        // Never show "Ready for pickup" if already ready!
        if (actions.includes('mark_ready_for_pickup') && !isAlreadyReady) {
            buttons.push(`<button class="${ghost}" onclick="FMOrders.run('ready-for-pickup', 'Ready for pickup')"><i class="fas fa-box-open"></i>Ready for pickup</button>`);
        }

        if (actions.includes('reject_payment')) {
            buttons.push(`<button class="${danger}" onclick="FMOrders.run('reject-payment', 'Decline payment')"><i class="fas fa-xmark"></i>Decline payment</button>`);
        } else if (actions.includes('cancel')) {
            buttons.push(`<button class="${danger}" onclick="FMOrders.run('cancel', 'Cancel order')"><i class="fas fa-ban"></i>${isPaid ? 'Cancel & Refund' : 'Cancel order'}</button>`);
        }

        if (order.can_refund || actions.includes('process_refund')) {
            if (order.payment_method === 'paymongo') {
                buttons.push(`<button class="${solid}" style="background: #2563eb;" onclick="FMOrders.refundPaymongo()"><i class="fas fa-rotate"></i>Process PayMongo Refund</button>`);
            } else {
                buttons.push(`<button class="${solid}" style="background: #059669;" onclick="FMOrders.refundManual()"><i class="fas fa-hand-holding-dollar"></i>Mark Refund as Sent</button>`);
            }
        }

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
                ${order.status === 'overdue' ? `
                    <div style="margin-bottom: 14px; padding: 12px 14px; background: rgba(220, 38, 38, 0.08); border: 1px solid rgba(220, 38, 38, 0.3); border-radius: 8px; color: var(--danger, #dc2626); font-size: 13px;">
                        <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <i class="fas fa-clock"></i> Overdue Reservation
                        </div>
                        <div>The scheduled pick-up date has passed. Please choose an action below: Extend pick-up date, Forfeit downpayment & relist item, or Mark as Sold.</div>
                    </div>
                ` : ''}

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
                    ${(order.payment_method === 'cash' && Number(order.downpayment_amount) > 0)
                        ? `${row('Required downpayment (50%)', peso(order.downpayment_amount))}
                           ${row('Due at counter pickup', peso(Number(order.amount_due) - Number(order.downpayment_amount)))}`
                        : ''}
                    ${row('Payment', methodLabel(order.payment_method))}
                    ${order.payment_reference ? row('Reference', order.payment_reference) : ''}
                    ${order.pickup_date
                        ? row('Pick-up schedule', `${esc(order.pickup_date_formatted || when(order.pickup_date))}${!['completed', 'cancelled', 'rejected'].includes(order.status) ? ` <button type="button" class="fm-btn ghost sm" style="padding: 2px 8px; font-size: 11px; margin-left: 8px;" onclick="FMOrders.extendDate()"><i class="fas fa-edit"></i> Edit</button>` : ''}`, false, true)
                        : (!['completed', 'cancelled', 'rejected'].includes(order.status)
                            ? row('Pick-up schedule', `<button type="button" class="fm-btn ghost sm" style="padding: 2px 8px; font-size: 11px;" onclick="FMOrders.extendDate()"><i class="fas fa-plus"></i> Set schedule</button>`, false, true)
                            : '')}
                    ${order.reserved_until ? row('Reserved until', when(order.reserved_until)) : ''}
                    ${order.reward_points_to_credit ? row('Buyer earns', `${order.reward_points_to_credit} point(s)${order.reward_points_credited ? ' (credited)' : ''}`) : ''}
                    ${order.completed_at ? row('Completed', when(order.completed_at)) : ''}
                </div>

                ${order.cancel_reason ? `<p style="margin: 12px 0 0; padding: 8px 10px; background: var(--danger-bg); border-radius: 6px; font-size: 12.5px; color: var(--danger);">${esc(order.cancel_reason)}</p>` : ''}

                ${order.refund_status ? `
                    <div style="margin-top: 12px; padding: 12px 14px; background: ${order.refund_status === 'completed' ? '#ecfdf5' : '#eff6ff'}; border: 1px solid ${order.refund_status === 'completed' ? '#a7f3d0' : '#bfdbfe'}; border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span style="font-weight: 700; font-size: 13px; color: ${order.refund_status === 'completed' ? '#065f46' : '#1e40af'};">
                                <i class="fas ${order.refund_status === 'completed' ? 'fa-check-circle' : 'fa-spinner fa-spin'}" style="margin-right: 5px;"></i>
                                Refund ${order.refund_status === 'completed' ? 'Completed' : (order.refund_status === 'failed' ? 'Failed' : 'Processing')}
                            </span>
                            <span style="font-weight: 700; color: ${order.refund_status === 'completed' ? '#059669' : '#2563eb'};">${esc(order.refund_amount_formatted || peso(order.refund_amount))}</span>
                        </div>
                        <p style="margin: 0; font-size: 12px; color: ${order.refund_status === 'completed' ? '#047857' : '#1e3a8a'};">
                            ${order.refund_status === 'completed'
                                ? `Refund was processed successfully${order.refund_reference ? ` (Ref: ${esc(order.refund_reference)})` : ''}${order.refunded_at ? ` on ${esc(order.refunded_at)}` : ''}.`
                                : `The refund is currently processing${order.refund_reference ? ` (Ref: ${esc(order.refund_reference)})` : ''}. Status will update automatically when PayMongo webhook is received.`}
                        </p>
                    </div>
                ` : ''}

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
            } else if (field?.type === 'date') {
                fieldHtml = `<input id="orderAskInput" type="date" class="fm-input" style="margin-top: 10px;" min="${new Date().toISOString().split('T')[0]}">`;
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
        let value = true;
        if (input) value = input.type === 'file' ? (input.files[0] || null) : input.value;

        document.getElementById('orderAskModal').style.display = 'none';
        const resolve = askResolve;
        askResolve = null;
        if (resolve) resolve(confirmed ? (value !== '' ? value : true) : null);
    }

    async function run(endpoint, label) {
        if (!current || busy) return;

        const needsReason = endpoint === 'reject-payment' || endpoint === 'cancel';
        let body = null;
        let isForm = false;

        if (needsReason) {
            const isPaid = current.payment_status === 'verified'
                || current.payment_status === 'partially_paid'
                || !!current.has_paid_downpayment
                || !!current.has_paid
                || ['verified', 'paid'].includes(current.downpayment_status);
            const cancelTitle = endpoint === 'cancel' && isPaid ? 'Cancel order & refund?' : label;
            const cancelBody = endpoint === 'cancel' && isPaid
                ? `This order is already paid (${peso(current.amount_due)}). Confirming will cancel the order, restore points, and initiate a refund. Please state the reason for the buyer:`
                : 'The buyer is told in their chat. Give them the reason.';

            const reason = await ask({
                title: cancelTitle,
                body: cancelBody,
                field: { label: 'Reason', type: 'textarea', placeholder: 'Shown to the buyer' },
                confirmLabel: endpoint === 'cancel' && isPaid ? 'Cancel & Refund' : label,
                danger: true,
            });
            if (reason === null) return;
            if (!String(reason).trim()) { showToast('A reason is required.', 'error'); return; }
            body = JSON.stringify({ reason: String(reason).trim() });
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

    function open(order, changeHandler, options) {
        current = order;
        onChange = changeHandler || null;
        readOnly = !!(options && options.readOnly);
        render();
    }

    function close() {
        document.getElementById('orderModal').style.display = 'none';
        current = null;
    }

    /**
     * Handing the item over is a counter moment, not a yes/no box: the buyer
     * is photographed receiving it. The panel carries the QR that moves the
     * job to a phone with a camera, and the same thing done here.
     */
    function handover() {
        if (!current) return;

        if (typeof window.openPickup !== 'function') {
            // A page that did not include the panel still completes, the way
            // it always did.
            run('complete', 'Complete handover');
            return;
        }

        openPickup(current, (completed) => {
            current = completed || current;
            render();
            if (typeof onChange === 'function') onChange(current);
        });
    }

    async function extendDate() {
        if (!current || busy) return;
        const newDate = await FMPickupDate.pick({
            current: current.pickup_date,
            title: 'Extend pick-up date',
        });
        if (!newDate) return;

        busy = true;
        try {
            const response = await fetch(`${API}/admin/transactions/${current.transaction_id}/pickup-schedule`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token()}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ pickup_date: newDate }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            current = payload.data || current;
            render();
            showToast('Pick-up date extended successfully', 'success');
            if (typeof onChange === 'function') onChange(current);
        } catch (error) {
            showToast(`Could not extend date: ${error.message}`, 'error');
        } finally {
            busy = false;
        }
    }

    async function forfeitRelist(refund = false) {
        if (!current || busy) return;
        const isPaid = current.payment_status === 'verified'
            || current.payment_status === 'partially_paid'
            || !!current.has_paid_downpayment
            || !!current.has_paid
            || ['verified', 'paid'].includes(current.downpayment_status);
        const isUnpaid = !isPaid;
        const hasRemainingBalance = Number(current.remaining_balance) > 0;

        let confirmed;
        if (refund && isPaid) {
            const refundAmt = current.refund_amount_formatted || peso(current.amount_due);
            confirmed = await ask({
                title: 'Cancel, Refund & Relist?',
                body: `Are you sure? This will cancel the overdue order, return the item to the public marketplace, and automatically initiate a refund of ${esc(refundAmt)} back to the buyer via PayMongo.`,
                confirmLabel: 'Refund & Relist',
                danger: true,
            });
        } else {
            confirmed = await ask({
                title: isUnpaid ? 'Cancel & Return to Marketplace?' : (hasRemainingBalance ? 'Forfeit DP & Relist Item?' : 'Forfeit Payment & Relist Item?'),
                body: isUnpaid
                    ? 'This overdue reservation was unpaid. Confirming will cancel this order and immediately return the item to the public marketplace.'
                    : (hasRemainingBalance
                        ? 'Are you sure? This will forfeit the downpayment, cancel this overdue transaction, and immediately return the item to the public marketplace without refund.'
                        : 'Are you sure? This will forfeit the payment, cancel this overdue transaction, and immediately return the item to the public marketplace without refund.'),
                confirmLabel: isUnpaid ? 'Return to Marketplace' : 'Forfeit & Relist',
                danger: true,
            });
        }
        if (!confirmed) return;

        busy = true;
        try {
            const response = await fetch(`${API}/admin/transactions/${current.transaction_id}/forfeit`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token()}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ refund: !!refund }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            current = payload.data || current;
            render();
            showToast(payload.message || (refund ? 'Order cancelled, refund initiated, and item relisted' : (isPaid ? 'Payment forfeited and item returned to marketplace' : 'Order cancelled and item returned to marketplace')), 'success');
            if (typeof onChange === 'function') onChange(current);
        } catch (error) {
            showToast(`Could not forfeit and relist: ${error.message}`, 'error');
        } finally {
            busy = false;
        }
    }

    async function refundPaymongo() {
        if (!current || busy) return;
        const refundAmt = current.refund_amount_formatted || peso(current.refund_amount || current.amount_due);
        const confirmed = await ask({
            title: 'Process PayMongo Refund?',
            body: `This will request a refund of ${esc(refundAmt)} via the PayMongo API. Funds will be returned directly to the buyer's account.`,
            confirmLabel: 'Process Refund',
            danger: false,
        });
        if (!confirmed) return;

        busy = true;
        try {
            const response = await fetch(`${API}/admin/transactions/${current.transaction_id}/refund`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token()}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'paymongo' }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            current = payload.data || current;
            render();
            showToast(payload.message || 'PayMongo refund submitted successfully', 'success');
            if (typeof onChange === 'function') onChange(current);
        } catch (error) {
            showToast(`Could not process refund: ${error.message}`, 'error');
        } finally {
            busy = false;
        }
    }

    async function refundManual() {
        if (!current || busy) return;
        const refundAmt = current.refund_amount_formatted || peso(current.refund_amount || current.amount_due);
        const ref = await ask({
            title: 'Mark Refund as Sent',
            body: `Confirm that you have manually transferred ${esc(refundAmt)} back to the buyer (via GCash/Cash). Enter the transaction reference number:`,
            field: { label: 'GCash / Payment Reference Number', type: 'textarea', placeholder: 'e.g. GCash Ref No. 1002348572' },
            confirmLabel: 'Confirm Refund Sent',
            danger: false,
        });
        if (ref === null) return;
        if (!String(ref).trim()) { showToast('A reference number is required.', 'error'); return; }

        busy = true;
        try {
            const response = await fetch(`${API}/admin/transactions/${current.transaction_id}/refund`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${token()}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ action: 'manual', reference_number: String(ref).trim() }),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            current = payload.data || current;
            render();
            showToast(payload.message || 'Refund marked as completed successfully', 'success');
            if (typeof onChange === 'function') onChange(current);
        } catch (error) {
            showToast(`Could not mark refund: ${error.message}`, 'error');
        } finally {
            busy = false;
        }
    }

    return { open, close, run, handover, extendDate, forfeitRelist, refundPaymongo, refundManual, askDone, peso, methodLabel, paymentBadge, statusBadge, when, esc, attr };
})();
</script>
@endpush
