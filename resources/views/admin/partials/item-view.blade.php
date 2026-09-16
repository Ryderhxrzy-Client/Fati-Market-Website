{{--
    Looking at an item, every inventory page sharing one window.

    Each page used to carry its own copy, and every copy showed photos[0] and
    stopped there: a listing with five pictures looked exactly like a listing
    with one, and the four the store had never seen were the ones a buyer would.
    This shows all of them - a large one, the rest as thumbnails, arrow keys
    and swipes between - alongside the figures that matter for the item's
    stage, the counter's proof photographs included.

    Include once per page, then call openItemView(item).
--}}

<div id="itemViewModal" class="modal-overlay" onclick="if (event.target === this) closeItemView()">
    <div class="modal modal-lg" style="max-width: 720px;">
        <div class="flex items-center justify-between mb-4">
            <h3 id="iv-title">Item details</h3>
            <button onclick="closeItemView()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="iv-body" class="space-y-4"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    let photos = [];
    let index = 0;

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
        if (amount === null || amount === undefined || amount === '' || !isFinite(Number(amount))) return null;
        return '₱' + Number(amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function row(label, value) {
        if (value === null || value === undefined || value === '') return '';
        return `
            <div style="display: flex; justify-content: space-between; gap: 14px; padding: 7px 0; font-size: 13px; border-bottom: 1px solid var(--surface-sunk);">
                <span style="color: var(--ink-500);">${esc(label)}</span>
                <span style="font-weight: 600; text-align: right;">${esc(value)}</span>
            </div>`;
    }

    function when(value) {
        if (!value) return null;
        const date = new Date(String(value).replace(' ', 'T'));
        return isNaN(date.getTime()) ? String(value) : date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' });
    }

    /** The gallery: one large photo, the rest underneath, and nothing hidden. */
    function galleryHtml() {
        if (!photos.length) {
            return `
                <div style="height: 240px; border-radius: 12px; background: var(--surface-sunk); display: flex; align-items: center; justify-content: center; color: var(--ink-400);">
                    <i class="fas fa-image" style="font-size: 30px;"></i>
                </div>`;
        }

        const arrows = photos.length > 1 ? `
            <button type="button" onclick="itemViewStep(-1)" aria-label="Previous photo"
                    style="position: absolute; left: 8px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border-radius: 50%; border: none; background: rgba(17,24,39,0.6); color: white; cursor: pointer;">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button type="button" onclick="itemViewStep(1)" aria-label="Next photo"
                    style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px; border-radius: 50%; border: none; background: rgba(17,24,39,0.6); color: white; cursor: pointer;">
                <i class="fas fa-chevron-right"></i>
            </button>
            <span style="position: absolute; right: 10px; bottom: 10px; padding: 3px 9px; border-radius: 999px; background: rgba(17,24,39,0.7); color: white; font-size: 11.5px; font-weight: 600;">
                ${index + 1} / ${photos.length}
            </span>` : '';

        const strip = photos.length > 1 ? `
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;">
                ${photos.map((url, i) => `
                    <button type="button" onclick="itemViewShow(${i})" aria-label="Photo ${i + 1}"
                            style="flex-shrink: 0; padding: 0; border-radius: 8px; cursor: pointer; background: none;
                                   border: 2px solid ${i === index ? 'var(--brand-600)' : 'transparent'};">
                        <img src="${attr(url)}" alt="" style="width: 62px; height: 62px; object-fit: cover; border-radius: 6px; display: block;">
                    </button>`).join('')}
            </div>` : '';

        return `
            <div style="position: relative; border-radius: 12px; overflow: hidden; background: var(--surface-sunk);">
                <a href="${attr(photos[index])}" target="_blank" rel="noopener" title="Open full size">
                    <img src="${attr(photos[index])}" alt="" style="width: 100%; height: 300px; object-fit: contain; display: block; background: #0b0f0d;">
                </a>
                ${arrows}
            </div>
            ${strip}`;
    }

    function renderGallery() {
        const host = document.getElementById('iv-gallery');
        if (host) host.innerHTML = galleryHtml();
    }

    window.itemViewShow = function (next) {
        if (!photos.length) return;
        index = (next + photos.length) % photos.length;
        renderGallery();
    };

    window.itemViewStep = function (step) {
        window.itemViewShow(index + step);
    };

    window.openItemView = function (item) {
        photos = Array.isArray(item.photos) ? item.photos.filter(Boolean) : [];
        index = 0;

        const status = String(item.status || '').toLowerCase();
        const paid = item.seller_payout_status === 'paid';

        // A sold listing is read for its sale first: who bought it, how they
        // paid, what they paid and what they earned back. The item's own
        // figures are the store's side of the same story, so they stay below.
        const sale = item.sale || null;
        const markup = item.markup ?? (item.public_price && item.acquisition_price
            ? Number(item.public_price) - Number(item.acquisition_price)
            : null);

        document.getElementById('iv-title').textContent = item.title || ('Item #' + (item.item_id ?? ''));

        document.getElementById('iv-body').innerHTML = `
            <div id="iv-gallery" class="space-y-2">${galleryHtml()}</div>

            <div class="flex items-center gap-2 flex-wrap">
                <span class="fm-badge brand">${esc(status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown')}</span>
                ${item.is_turnover_verified ? '<span class="fm-badge success">Received</span>' : ''}
                ${item.seller_payout_status ? `<span class="fm-badge ${paid ? 'success' : 'warning'}">${paid ? 'Seller paid' : 'Seller unpaid'}</span>` : ''}
                ${photos.length ? `<span class="fm-badge">${photos.length} photo${photos.length === 1 ? '' : 's'}</span>` : '<span class="fm-badge warning">No photos</span>'}
                ${sale && sale.reward_points_earned ? `<span class="fm-badge reward">Buyer earned ${sale.reward_points_earned} point(s)</span>` : ''}
            </div>

            ${item.description ? `<p style="font-size: 13.5px; color: var(--ink-700); white-space: pre-line; margin: 0;">${esc(item.description)}</p>` : ''}

            ${sale ? `
                <div style="border: 1px solid var(--line); border-radius: 10px; padding: 12px; background: var(--surface-sunk);">
                    <p class="cell-sub" style="margin: 0 0 6px;">The sale</p>
                    ${row('Receipt', sale.receipt_no)}
                    ${row('Buyer', sale.buyer_name || sale.buyer_email)}
                    ${row('Payment method', sale.payment_method ? sale.payment_method.charAt(0).toUpperCase() + sale.payment_method.slice(1) : null)}
                    ${row('Payment status', sale.payment_status ? sale.payment_status.replace(/_/g, ' ').replace(/^./, c => c.toUpperCase()) : null)}
                    ${row('Item price', peso(sale.subtotal))}
                    ${row('Points used', sale.points_used ? `${sale.points_used} point(s) · -${peso(sale.points_discount_amount) || ''}` : null)}
                    ${row('Amount paid', peso(sale.amount_due))}
                    ${row('Buyer earned', sale.reward_points_earned ? sale.reward_points_earned + ' point(s)' : 'No points')}
                    ${row('Sold on', when(sale.completed_at))}
                </div>` : ''}

            <div>
                ${row('Item', '#' + (item.item_id ?? ''))}
                ${row('Seller', item.seller_email)}
                ${row('Asking price', peso(item.seller_asking_price))}
                ${row('Acquisition price', peso(item.acquisition_price))}
                ${row('Selling price', peso(item.public_price))}
                ${row('Markup', peso(markup))}
                ${row('Buyer earns', item.reward_points ? item.reward_points + ' point(s)' : null)}
                ${row('Seller payout', item.seller_payout_status ? (paid ? 'Paid ' + (peso(item.seller_payout_amount) || '') : 'Not paid yet') : null)}
                ${row('Received', when(item.acquired_at))}
                ${row('Published', when(item.published_at))}
                ${row('Offered', when(item.created_at))}
                ${row('Declined because', item.rejected_reason)}
            </div>

            ${item.turnover_photo || item.seller_payout_photo ? `
                <div>
                    <p class="cell-sub" style="margin: 0 0 6px;">The counter's proof</p>
                    <div style="display: flex; gap: 8px;">
                        ${item.seller_payout_photo ? `<a href="${attr(item.seller_payout_photo)}" target="_blank" rel="noopener" style="flex: 1;">
                            <img src="${attr(item.seller_payout_photo)}" alt="Seller paid" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px;"></a>` : ''}
                        ${item.turnover_photo ? `<a href="${attr(item.turnover_photo)}" target="_blank" rel="noopener" style="flex: 1;">
                            <img src="${attr(item.turnover_photo)}" alt="Item received" style="width: 100%; height: 120px; object-fit: cover; border-radius: 8px;"></a>` : ''}
                    </div>
                </div>` : ''}`;

        document.getElementById('itemViewModal').classList.add('active');
    };

    window.closeItemView = function () {
        document.getElementById('itemViewModal').classList.remove('active');
    };

    // Arrow keys walk the photos while the window is open, the way any
    // gallery does; Escape closes it.
    document.addEventListener('keydown', (event) => {
        if (!document.getElementById('itemViewModal').classList.contains('active')) return;
        if (event.key === 'Escape') closeItemView();
        if (event.key === 'ArrowLeft') itemViewStep(-1);
        if (event.key === 'ArrowRight') itemViewStep(1);
    });
})();
</script>
@endpush
