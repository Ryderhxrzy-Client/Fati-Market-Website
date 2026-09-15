{{--
    The item editor every inventory page shares: name, description, category,
    selling price, status, and a way into the photos. Replaces the per-page
    modal that could only touch status and price. Include at the END of a
    page: its showEditModal() takes over the page's own.
--}}

<div class="modal-overlay" id="itemEditModal">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3>Edit item</h3>
            <button onclick="closeItemEdit()" class="text-gray-500 hover:text-gray-700" aria-label="Close"><i class="fas fa-times text-xl"></i></button>
        </div>

        <form class="space-y-4" onsubmit="saveItemEdit(event)">
            <input type="hidden" id="ie-id">
            <div class="flex gap-3 items-start">
                <img id="ie-photo" alt="" style="width: 72px; height: 72px; border-radius: 8px; object-fit: cover; background: var(--surface-sunk); display: none;">
                <div class="min-w-0 flex-1">
                    <p class="cell-sub" id="ie-meta"></p>
                    <button type="button" class="fm-btn ghost sm" id="ie-photos" style="margin-top: 6px;" onclick="openItemEditPhotos()"><i class="fas fa-images"></i>Manage photos</button>
                </div>
            </div>
            <div>
                <label class="fm-label" for="ie-title">Name</label>
                <input id="ie-title" class="fm-input" required maxlength="255">
            </div>
            <div>
                <label class="fm-label" for="ie-description">Description</label>
                <textarea id="ie-description" class="fm-input" rows="3" maxlength="1000"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="fm-label" for="ie-category">Category</label>
                    <select id="ie-category" class="fm-input"><option value="">Loading…</option></select>
                </div>
                <div>
                    <label class="fm-label" for="ie-status">Status</label>
                    <select id="ie-status" class="fm-input">
                        <option value="pending">Negotiating (pending)</option>
                        <option value="public">Published</option>
                        <option value="reserved">Reserved</option>
                    </select>
                    <p class="cell-sub" id="ie-status-note" style="margin-top: 4px;"></p>
                </div>
            </div>
            <div>
                <label class="fm-label" for="ie-price">Public selling price (₱)</label>
                <input id="ie-price" class="fm-input" inputmode="decimal" placeholder="e.g. 250.00">
                <p class="cell-sub" style="margin-top: 4px;">What a buyer pays. Needed to publish; the acquisition price is set in the workflow.</p>
            </div>
            <p id="ie-error" style="display: none; color: var(--danger); font-size: 13px; margin: 0;"></p>
            <div class="flex gap-3 justify-end pt-2">
                <button type="button" onclick="closeItemEdit()" class="fm-btn ghost">Cancel</button>
                <button type="submit" id="ie-save" class="fm-btn primary"><i class="fas fa-save"></i>Save changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const API = 'https://fati-api.alertaraqc.com/api';
    let current = null;
    let categoriesLoaded = false;

    function ieToken() {
        return document.querySelector('meta[name="api-token"]')?.getAttribute('content')
            || sessionStorage.getItem('admin_token') || localStorage.getItem('admin_token') || '';
    }

    async function loadCategories(selectedId) {
        const select = document.getElementById('ie-category');
        if (!categoriesLoaded) {
            try {
                const response = await fetch(`${API}/categories`, { headers: { 'Authorization': `Bearer ${ieToken()}`, 'Accept': 'application/json' } });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
                const rows = payload.data || [];
                select.innerHTML = '<option value="">Keep current category</option>' + rows.map(c =>
                    `<option value="${Number(c.category_id)}">${String(c.name || '').replace(/</g, '&lt;')}</option>`).join('');
                categoriesLoaded = true;
            } catch (error) {
                select.innerHTML = '<option value="">Categories unavailable</option>';
            }
        }
        select.value = selectedId ? String(selectedId) : '';
        if (select.value !== String(selectedId || '')) select.value = '';
    }

    /** The editor itself. The Edit buttons call openItemEdit(), never the page-level showEditModal(). */
    function openEditor(item) {
        current = item;
        const status = String(item.status || 'pending').toLowerCase();
        const photo = Array.isArray(item.photos) && item.photos.length ? item.photos[0] : null;

        document.getElementById('ie-id').value = item.item_id;
        document.getElementById('ie-title').value = item.title || '';
        document.getElementById('ie-description').value = item.description || '';
        document.getElementById('ie-price').value = item.public_price || '';
        document.getElementById('ie-meta').textContent = `Item #${item.item_id} · ${item.seller_email || ''} · ${status}`;
        const img = document.getElementById('ie-photo');
        if (photo) { img.src = photo; img.style.display = 'block'; } else { img.style.display = 'none'; }
        document.getElementById('ie-photos').style.display = typeof window.openItemWorkflow === 'function' ? 'inline-flex' : 'none';

        // acquired and sold are reached through the counter and checkout, not
        // this form: keep the item's own value selectable, nothing else.
        const statusSelect = document.getElementById('ie-status');
        const note = document.getElementById('ie-status-note');
        [...statusSelect.options].forEach(o => { if (['acquired', 'sold', 'rejected'].includes(o.value)) o.remove(); });
        if (['acquired', 'sold', 'rejected'].includes(status)) {
            const own = document.createElement('option');
            own.value = status; own.textContent = status === 'acquired' ? 'Acquired' : status === 'sold' ? 'Sold' : 'Rejected';
            statusSelect.prepend(own);
            note.textContent = status === 'acquired' ? 'Acquired items are published from here; "sold" comes from a completed order.'
                : 'This status is set by the store\'s process, not by hand.';
        } else {
            note.textContent = '';
        }
        statusSelect.value = status;
        if (statusSelect.value !== status) statusSelect.value = 'pending';

        document.getElementById('ie-error').style.display = 'none';
        document.getElementById('itemEditModal').classList.add('active');
        loadCategories(item.category_id);
    }

    window.openItemEdit = openEditor;

    window.closeItemEdit = function () {
        document.getElementById('itemEditModal').classList.remove('active');
    };

    window.openItemEditPhotos = function () {
        if (!current || typeof window.openItemWorkflow !== 'function') return;
        closeItemEdit();
        window.openItemWorkflow(current.item_id);
    };

    window.saveItemEdit = async function (event) {
        event.preventDefault();
        if (!current) return;

        const error = document.getElementById('ie-error');
        const button = document.getElementById('ie-save');
        const title = document.getElementById('ie-title').value.trim();
        const description = document.getElementById('ie-description').value.trim();
        const categoryId = document.getElementById('ie-category').value;
        const status = document.getElementById('ie-status').value;
        const price = document.getElementById('ie-price').value.trim();

        if (!title) { error.textContent = 'Give the item a name.'; error.style.display = 'block'; return; }
        if (price && isNaN(Number(price))) { error.textContent = 'Enter a valid peso amount, e.g. 250 or 249.50.'; error.style.display = 'block'; return; }
        if (status === 'public' && !price) { error.textContent = 'A public selling price is required to publish.'; error.style.display = 'block'; return; }

        // Only what changed, so an untouched status or price is never re-sent.
        const body = {};
        if (title !== (current.title || '')) body.title = title;
        if (description !== (current.description || '')) body.description = description;
        if (categoryId && Number(categoryId) !== Number(current.category_id)) body.category_id = Number(categoryId);
        if (status !== String(current.status || '').toLowerCase()) body.status = status;
        if (price && price !== String(current.public_price || '')) body.public_price = price;
        if (status === 'public' && price && !body.public_price && !current.public_price) body.public_price = price;

        if (Object.keys(body).length === 0) { closeItemEdit(); return; }

        button.disabled = true;
        error.style.display = 'none';
        try {
            const response = await fetch(`${API}/admin/items/${current.item_id}`, {
                method: 'PUT',
                headers: { 'Authorization': `Bearer ${ieToken()}`, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
                throw new Error(errors || payload.message || `HTTP ${response.status}`);
            }
            closeItemEdit();
            if (typeof showToast === 'function') showToast('Item updated', 'success');
            setTimeout(() => location.reload(), 600);
        } catch (e) {
            error.textContent = e.message;
            error.style.display = 'block';
        } finally {
            button.disabled = false;
        }
    };

    document.getElementById('itemEditModal').addEventListener('click', function (e) { if (e.target === this) closeItemEdit(); });
})();
</script>
@endpush
