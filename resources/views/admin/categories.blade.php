@extends('layouts.admin-dashboard')

@section('title', 'Categories')
@section('subtitle', 'How students file the items they list')

@section('actions')
    <button onclick="openCategoryModal()" class="fm-btn primary">
        <i class="fas fa-plus"></i>Add category
    </button>
@endsection

@section('content')
<div class="space-y-6">
    <div id="categoriesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
</div>

<!-- Add / edit -->
<div class="modal-overlay" id="categoryModal">
    <div class="modal">
        <div class="flex items-center justify-between mb-4">
            <h3 id="categoryModalTitle">Add category</h3>
            <button onclick="closeCategoryModal()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form class="space-y-4" onsubmit="saveCategory(event)">
            <input type="hidden" id="categoryId">
            <div>
                <label class="fm-label" for="categoryName">Name</label>
                <input type="text" id="categoryName" placeholder="e.g. Textbooks" class="fm-input" required maxlength="255">
            </div>
            <div>
                <label class="fm-label" for="categoryDescription">Description <span style="font-weight: 400; color: var(--ink-500);">(optional)</span></label>
                <textarea id="categoryDescription" placeholder="What belongs here" rows="3" class="fm-input"></textarea>
            </div>
            <p id="categoryError" class="text-sm" style="color: var(--danger); display: none; margin: 0;"></p>
            <div class="flex gap-2 pt-2 justify-end">
                <button type="button" onclick="closeCategoryModal()" class="fm-btn ghost">Cancel</button>
                <button type="submit" id="categorySave" class="fm-btn primary">Save category</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3 class="mb-2">Delete this category?</h3>
        <p id="deleteBody" style="font-size: 13.5px; color: var(--ink-600);"></p>
        <div class="flex gap-2 pt-4 justify-end">
            <button type="button" onclick="closeDeleteModal()" class="fm-btn ghost">Cancel</button>
            <button type="button" id="deleteConfirm" class="fm-btn danger" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    /*
     * The add, edit and delete buttons on this page used to do nothing - the
     * form had no handler. They now go through the same admin endpoints the
     * mobile console uses, and the grid re-reads itself after each change.
     */
    const API = 'https://fati-api.alertaraqc.com/api';
    const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') || '';
    const headers = {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    };

    let categories = @json($categories ?? []);
    let deleting = null;
    let busy = false;

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function render() {
        const grid = document.getElementById('categoriesGrid');

        if (!categories.length) {
            grid.innerHTML = `
                <div class="col-span-full">
                    <div class="fm-card">
                        <div class="fm-empty">
                            <i class="fas fa-tags"></i>
                            <p>No categories yet</p>
                            <span>Categories are how students file the items they list.</span>
                            <div class="mt-4">
                                <button onclick="openCategoryModal()" class="fm-btn primary"><i class="fas fa-plus"></i>Create the first category</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            return;
        }

        grid.innerHTML = categories.map(category => {
            const count = Number(category.item_count ?? category.items_count ?? 0);
            return `
                <div class="fm-card fm-card-hover">
                    <div class="fm-card-body">
                        <div class="flex items-start justify-between gap-3">
                            <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);"><i class="fas fa-tag"></i></div>
                            <span class="fm-badge ${count > 0 ? 'brand' : ''}">${count} item${count === 1 ? '' : 's'}</span>
                        </div>
                        <h3 style="margin-top: 12px;">${escapeHtml(category.name || 'Unnamed')}</h3>
                        <p style="font-size: 13px; color: var(--ink-600); margin-top: 4px; min-height: 20px;">${escapeHtml(category.description || 'No description')}</p>
                        <div class="flex gap-2 mt-4">
                            <button class="fm-btn ghost sm flex-1" onclick="openCategoryModal(${Number(category.category_id)})">
                                <i class="fas fa-pen"></i>Edit
                            </button>
                            <button class="fm-btn danger sm flex-1" onclick="openDeleteModal(${Number(category.category_id)})">
                                <i class="fas fa-trash"></i>Delete
                            </button>
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    async function reload() {
        try {
            const response = await fetch(`${API}/categories`, { headers });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);
            categories = payload.data || [];
            render();
        } catch (error) {
            showToast(`Could not refresh categories: ${error.message}`, 'error');
        }
    }

    function openCategoryModal(categoryId) {
        const existing = categoryId ? categories.find(c => Number(c.category_id) === Number(categoryId)) : null;
        document.getElementById('categoryModalTitle').textContent = existing ? 'Edit category' : 'Add category';
        document.getElementById('categoryId').value = existing ? existing.category_id : '';
        document.getElementById('categoryName').value = existing ? (existing.name || '') : '';
        document.getElementById('categoryDescription').value = existing ? (existing.description || '') : '';
        document.getElementById('categoryError').style.display = 'none';
        document.getElementById('categoryModal').classList.add('active');
        setTimeout(() => document.getElementById('categoryName').focus(), 60);
    }

    function closeCategoryModal() {
        document.getElementById('categoryModal').classList.remove('active');
    }

    async function saveCategory(event) {
        event.preventDefault();
        if (busy) return;

        const id = document.getElementById('categoryId').value;
        const name = document.getElementById('categoryName').value.trim();
        const description = document.getElementById('categoryDescription').value.trim();
        const error = document.getElementById('categoryError');
        const button = document.getElementById('categorySave');

        if (!name) {
            error.textContent = 'Give the category a name.';
            error.style.display = 'block';
            return;
        }

        busy = true;
        button.disabled = true;
        error.style.display = 'none';

        try {
            const body = { name };
            if (description) body.description = description;

            const response = await fetch(id ? `${API}/admin/categories/${id}` : `${API}/admin/categories`, {
                method: id ? 'PUT' : 'POST',
                headers,
                body: JSON.stringify(body),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const errors = payload.errors ? Object.values(payload.errors).flat().join(' ') : '';
                throw new Error(errors || payload.message || `HTTP ${response.status}`);
            }

            closeCategoryModal();
            showToast(id ? 'Category updated' : 'Category created', 'success');
            await reload();
        } catch (e) {
            error.textContent = e.message;
            error.style.display = 'block';
        } finally {
            busy = false;
            button.disabled = false;
        }
    }

    function openDeleteModal(categoryId) {
        deleting = categories.find(c => Number(c.category_id) === Number(categoryId));
        if (!deleting) return;

        const count = Number(deleting.item_count ?? deleting.items_count ?? 0);
        document.getElementById('deleteBody').textContent = count > 0
            ? `"${deleting.name}" still holds ${count} item${count === 1 ? '' : 's'}. Move them somewhere else first - the server will refuse until then.`
            : `Nothing is filed under "${deleting.name}", so it can go.`;
        document.getElementById('deleteModal').classList.add('active');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('active');
        deleting = null;
    }

    async function confirmDelete() {
        if (!deleting || busy) return;
        busy = true;
        const button = document.getElementById('deleteConfirm');
        button.disabled = true;

        try {
            const response = await fetch(`${API}/admin/categories/${deleting.category_id}`, { method: 'DELETE', headers });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || `HTTP ${response.status}`);

            showToast(`"${deleting.name}" deleted`, 'success');
            closeDeleteModal();
            await reload();
        } catch (e) {
            showToast(e.message, 'error');
        } finally {
            busy = false;
            button.disabled = false;
        }
    }

    document.getElementById('categoryModal').addEventListener('click', function (e) { if (e.target === this) closeCategoryModal(); });
    document.getElementById('deleteModal').addEventListener('click', function (e) { if (e.target === this) closeDeleteModal(); });

    render();
</script>
@endpush
@endsection
