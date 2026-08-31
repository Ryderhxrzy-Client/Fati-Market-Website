{{--
    The acquisition workflow, shared by every inventory page.

    Before this, the website could only edit an item's title and shove a price
    into the legacy `markup_points` field, which skipped the whole cash
    pipeline: what Ofelia agreed to pay the seller, the physical turnover that
    entitles the seller to that cash, the payout itself, and the reward-point
    preview shown before publishing. Those are the steps the mobile admin app
    offers, so they are offered here from the same endpoints.

    Include this once per page and call openItemWorkflow(itemId).
--}}

<div id="workflowModal"
     onclick="if (event.target === this) closeItemWorkflow()"
     style="display: none; position: fixed; inset: 0; background: rgba(17,24,39,0.82); z-index: 70; align-items: center; justify-content: center; padding: 32px;">
    <div style="background: white; border-radius: 12px; max-width: 620px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #1f2937;">Item workflow</h4>
            <button onclick="closeItemWorkflow()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #6b7280;">&times;</button>
        </div>
        <div id="workflowBody" style="padding: 20px;"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const WF_API = 'https://fati-api.alertaraqc.com/api';

    let workflowItem = null;
    let workflowBusy = false;

    function wfToken() {
        return document.querySelector('meta[name="api-token"]')?.getAttribute('content')
            || sessionStorage.getItem('admin_token')
            || localStorage.getItem('admin_token')
            || '';
    }

    function wfEscape(value) {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    }

    function wfAttr(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function wfPeso(amount) {
        if (amount === null || amount === undefined || amount === '') return '—';
        const value = Number(amount);
        if (!isFinite(value)) return '—';
        return '₱' + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function wfRow(label, value) {
        return `
            <div style="display: flex; justify-content: space-between; gap: 12px; font-size: 13px; padding: 4px 0;">
                <span style="color: #6b7280;">${wfEscape(label)}</span>
                <span style="color: #1f2937; font-weight: 600; text-align: right;">${wfEscape(value)}</span>
            </div>
        `;
    }

    /** One titled block of the workflow, with its own inputs and button. */
    function wfStep(title, hint, inner) {
        return `
            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-bottom: 12px;">
                <p style="margin: 0 0 2px 0; font-size: 13px; font-weight: 700; color: #1f2937;">${wfEscape(title)}</p>
                ${hint ? `<p style="margin: 0 0 10px 0; font-size: 12px; color: #6b7280;">${wfEscape(hint)}</p>` : ''}
                ${inner}
            </div>
        `;
    }

    const inputStyle = 'width: 100%; padding: 7px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; margin-bottom: 8px;';
    const buttonStyle = 'background: #16a34a; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;';
    const dangerStyle = 'background: white; color: #dc2626; border: 1px solid #dc2626; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;';

    window.openItemWorkflow = async function (itemId) {
        document.getElementById('workflowModal').style.display = 'flex';
        document.getElementById('workflowBody').innerHTML =
            '<p style="font-size: 13px; color: #6b7280;">Loading item…</p>';

        try {
            const response = await fetch(`${WF_API}/items/${itemId}`, {
                headers: { 'Authorization': `Bearer ${wfToken()}`, 'Accept': 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            workflowItem = payload.data;
            renderWorkflow();
        } catch (error) {
            document.getElementById('workflowBody').innerHTML =
                `<p style="font-size: 13px; color: #b91c1c;">Could not load the item: ${wfEscape(error.message)}</p>`;
        }
    };

    window.closeItemWorkflow = function () {
        document.getElementById('workflowModal').style.display = 'none';
        workflowItem = null;
    };

    function renderWorkflow() {
        const item = workflowItem;
        const status = (item.status || '').toLowerCase();
        const isPending = status === 'pending' || status === 'private';
        const isAcquired = status === 'acquired';
        const isPublic = status === 'public';
        const paid = item.seller_payout_status === 'paid';

        let html = `
            <p style="margin: 0 0 4px 0; font-size: 15px; font-weight: 700; color: #1f2937;">${wfEscape(item.title || ('Item #' + item.item_id))}</p>
            <p style="margin: 0 0 12px 0; font-size: 12px; color: #6b7280;">${wfEscape(item.seller_email || '')} &middot; ${wfEscape(status)}</p>

            <div style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px; margin-bottom: 16px; background: #f9fafb;">
                ${wfRow('Seller asking', wfPeso(item.seller_asking_price))}
                ${wfRow('Acquisition price', wfPeso(item.acquisition_price))}
                ${wfRow('Public price', wfPeso(item.public_price))}
                ${wfRow('Markup', wfPeso(item.markup))}
                ${wfRow('Turnover verified', item.is_turnover_verified ? 'Yes' : 'Not yet')}
                ${wfRow('Seller paid', paid ? wfPeso(item.seller_payout_amount) : 'Not yet')}
                ${item.meetup_schedule ? wfRow('Meet-up', item.meetup_schedule) : ''}
            </div>
        `;

        // ── Agreeing a price with the seller ─────────────────────────────
        if (isPending || isAcquired) {
            html += wfStep(
                'Acquisition price',
                'What the store agrees to pay the seller. Separate from the buyer price.',
                `<input id="wfAcquisitionPrice" type="text" inputmode="decimal" placeholder="e.g. 180.00"
                        value="${wfAttr(item.acquisition_price || '')}" style="${inputStyle}">
                 <button style="${buttonStyle}" onclick="wfSetAcquisitionPrice()">Save price</button>`
            );

            html += wfStep(
                'Meet-up schedule',
                'When the seller brings the item in.',
                `<input id="wfMeetup" type="datetime-local" value="${wfAttr(wfToLocalInput(item.meetup_schedule))}" style="${inputStyle}">
                 <button style="${buttonStyle}" onclick="wfSetMeetup()">Save schedule</button>`
            );
        }

        // ── Physically receiving it ──────────────────────────────────────
        if (!item.is_turnover_verified) {
            html += wfStep(
                'Mark acquired',
                'Confirms the item is physically in the store - what entitles the seller to their cash. Attach the counter proof: the item received, the seller paid.',
                `<input id="wfTurnoverPrice" type="text" inputmode="decimal" placeholder="Acquisition price (optional)"
                        value="${wfAttr(item.acquisition_price || '')}" style="${inputStyle}">
                 <input id="wfPayoutAmount" type="text" inputmode="decimal" placeholder="Payout amount (optional)" style="${inputStyle}">
                 <input id="wfTurnoverNotes" type="text" placeholder="Notes (optional)" style="${inputStyle}">
                 <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 2px;">Proof: item received</label>
                 <input id="wfProofItem" type="file" accept="image/*" style="${inputStyle}">
                 <label style="display: block; font-size: 12px; color: #6b7280; margin-bottom: 2px;">Proof: seller paid</label>
                 <input id="wfProofPayout" type="file" accept="image/*" style="${inputStyle}">
                 <button style="${buttonStyle}" onclick="wfVerifyTurnover()">Confirm &amp; proof</button>`
            );
        }

        // ── Paying the seller ────────────────────────────────────────────
        if (item.is_turnover_verified && !paid) {
            html += wfStep(
                'Record seller payout',
                'Cash handed to the seller. Leave the amount blank to use the agreed acquisition price.',
                `<input id="wfPayout" type="text" inputmode="decimal" placeholder="${wfAttr(item.acquisition_price || 'Amount')}" style="${inputStyle}">
                 <button style="${buttonStyle}" onclick="wfRecordPayout()">Mark seller paid</button>`
            );
        }

        // ── Putting it on sale ───────────────────────────────────────────
        if (!isPublic) {
            html += wfStep(
                'Publish to the catalog',
                'Check the preview first: it shows the markup and the reward points the buyer will earn.',
                `<input id="wfPublicPrice" type="text" inputmode="decimal" placeholder="Public selling price"
                        value="${wfAttr(item.public_price || '')}" style="${inputStyle}">
                 <div id="wfPreview" style="font-size: 12px; color: #374151; margin-bottom: 8px;"></div>
                 <button style="${buttonStyle}" onclick="wfPreviewPublish()">Preview</button>
                 <button style="${buttonStyle} margin-left: 6px;" onclick="wfPublish()">Publish</button>`
            );
        } else {
            html += wfStep(
                'Remove from the catalog',
                'Takes the listing down. Buyers can no longer check it out.',
                `<button style="${dangerStyle}" onclick="wfUnpublish()">Unpublish</button>`
            );
        }

        // ── Turning it down ──────────────────────────────────────────────
        if (isPending) {
            html += wfStep(
                'Reject the offer',
                'The seller is told why, in their conversation for this item.',
                `<input id="wfRejectReason" type="text" placeholder="Reason" style="${inputStyle}">
                 <button style="${dangerStyle}" onclick="wfReject()">Reject</button>`
            );
        }

        document.getElementById('workflowBody').innerHTML = html;
    }

    /** The API sends ISO timestamps; the datetime-local input wants no zone. */
    function wfToLocalInput(value) {
        if (!value) return '';
        const date = new Date(value);
        if (isNaN(date.getTime())) return '';
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    /**
     * Every workflow call goes through here so a success always leaves the
     * panel showing the item the server just returned, rather than what the
     * page assumed would happen.
     */
    async function wfPost(path, body, successMessage) {
        if (workflowBusy) return;
        workflowBusy = true;

        try {
            const response = await fetch(`${WF_API}/admin/items/${workflowItem.item_id}/${path}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${wfToken()}`,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(body),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            workflowItem = payload.data || workflowItem;
            renderWorkflow();
            wfNotify(successMessage, 'success');
        } catch (error) {
            wfNotify(error.message, 'error');
        } finally {
            workflowBusy = false;
        }
    }

    /** Use the host page's toast when it has one, otherwise just say it. */
    function wfNotify(message, tone) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, tone);
        } else if (tone === 'error') {
            alert(message);
        }
    }

    function wfValue(id) {
        return (document.getElementById(id)?.value || '').trim();
    }

    window.wfSetAcquisitionPrice = function () {
        const price = wfValue('wfAcquisitionPrice');
        if (!price) return wfNotify('Enter an acquisition price.', 'error');

        wfPost('acquisition-price', { acquisition_price: price }, 'Acquisition price saved');
    };

    window.wfSetMeetup = function () {
        const schedule = wfValue('wfMeetup');
        wfPost('meetup', { meetup_schedule: schedule || null }, 'Meet-up schedule saved');
    };

    window.wfVerifyTurnover = async function () {
        if (workflowBusy) return;
        workflowBusy = true;

        // Multipart, because the two proof photographs ride along with the
        // prices - the same payload the mobile scan screen sends.
        const body = new FormData();
        const price = wfValue('wfTurnoverPrice');
        const payout = wfValue('wfPayoutAmount');
        const notes = wfValue('wfTurnoverNotes');
        const proofItem = document.getElementById('wfProofItem')?.files[0];
        const proofPayout = document.getElementById('wfProofPayout')?.files[0];

        if (price) body.append('acquisition_price', price);
        if (payout) body.append('seller_payout_amount', payout);
        if (notes) body.append('notes', notes);
        if (proofItem) body.append('turnover_photo', proofItem);
        if (proofPayout) body.append('payout_photo', proofPayout);

        try {
            const response = await fetch(`${WF_API}/admin/items/${workflowItem.item_id}/verify-turnover`, {
                method: 'POST',
                headers: { 'Authorization': `Bearer ${wfToken()}`, 'Accept': 'application/json' },
                body,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            workflowItem = payload.data || workflowItem;
            renderWorkflow();
            wfNotify('Item marked acquired', 'success');
        } catch (error) {
            wfNotify(error.message, 'error');
        } finally {
            workflowBusy = false;
        }
    };

    window.wfRecordPayout = function () {
        const amount = wfValue('wfPayout');
        wfPost('seller-payout', amount ? { amount } : {}, 'Seller marked paid');
    };

    window.wfPreviewPublish = async function () {
        const price = wfValue('wfPublicPrice');
        if (!price) return wfNotify('Enter a public price first.', 'error');

        const preview = document.getElementById('wfPreview');
        preview.textContent = 'Checking…';

        try {
            const response = await fetch(
                `${WF_API}/admin/items/${workflowItem.item_id}/publish-preview?public_price=${encodeURIComponent(price)}`,
                { headers: { 'Authorization': `Bearer ${wfToken()}`, 'Accept': 'application/json' } }
            );
            const payload = await response.json();

            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            const data = payload.data || {};
            const blockers = data.blockers || [];

            preview.innerHTML = `
                ${wfRow('Public price', wfPeso(data.public_price))}
                ${wfRow('Acquisition', wfPeso(data.acquisition_price))}
                ${wfRow('Markup', wfPeso(data.markup))}
                ${wfRow('Buyer earns', `${data.reward_points || 0} point(s)`)}
                ${blockers.length
                    ? `<p style="color: #b91c1c; margin: 6px 0 0 0;">${blockers.map(wfEscape).join('<br>')}</p>`
                    : '<p style="color: #166534; margin: 6px 0 0 0;">Ready to publish.</p>'}
            `;
        } catch (error) {
            preview.innerHTML = `<span style="color: #b91c1c;">${wfEscape(error.message)}</span>`;
        }
    };

    window.wfPublish = function () {
        const price = wfValue('wfPublicPrice');
        if (!price) return wfNotify('Enter a public price.', 'error');

        wfPost('publish', { public_price: price }, 'Item published');
    };

    window.wfUnpublish = function () {
        if (!confirm('Remove this item from the catalog?')) return;

        wfPost('unpublish', {}, 'Item removed from the catalog');
    };

    window.wfReject = function () {
        const reason = wfValue('wfRejectReason');
        if (!reason) return wfNotify('Give the seller a reason.', 'error');

        wfPost('reject', { reason }, 'Offer rejected');
    };
})();
</script>
@endpush
