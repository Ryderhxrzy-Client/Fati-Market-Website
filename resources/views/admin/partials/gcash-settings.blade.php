<section class="fm-card" aria-labelledby="gcash-heading">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 id="gcash-heading">GCash payment settings</h3>
        <p>The account and QR shown to buyers when they choose Pay with GCash.</p>
    </div>
    <div class="p-6 space-y-4">
        @if(session('gcash_success'))
            <p role="status" class="text-green-700">{{ session('gcash_success') }}</p>
        @endif
        @if($errors->any())
            <div role="alert" class="text-red-600">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        @if($gcashError)
            <p role="alert" class="text-red-600">{{ $gcashError }}</p>
            <a href="{{ route('admin.settings') }}" class="fm-btn">Retry</a>
        @else
            <form action="{{ route('admin.settings.gcash.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="gcash-name" class="block mb-2">GCash account name</label>
                    <input id="gcash-name" name="account_name" class="fm-input" required maxlength="255" value="{{ old('account_name', $gcash['account_name'] ?? '') }}">
                </div>
                <div>
                    <label for="gcash-number" class="block mb-2">GCash mobile number</label>
                    <input id="gcash-number" name="account_number" type="tel" class="fm-input" required pattern="(09[0-9]{9}|\+639[0-9]{9})" placeholder="09XXXXXXXXX" value="{{ old('account_number', $gcash['account_number'] ?? '') }}">
                </div>
                @if(!empty($gcash['qr_image_url']))
                    <img src="{{ $gcash['qr_image_url'] }}" alt="Current GCash payment QR code" style="width:240px;max-width:100%;height:240px;object-fit:contain;background:white;padding:12px">
                    <label class="flex gap-2 items-center"><input type="checkbox" name="remove_qr" value="1"> Remove current QR code</label>
                @endif
                <div>
                    <label for="gcash-qr" class="block mb-2">Upload or replace QR code</label>
                    <input id="gcash-qr" name="qr_image" type="file" accept="image/png,image/jpeg" class="fm-input">
                    <p class="text-sm mt-2">PNG or JPG, up to 5 MB. Leave empty to keep the current QR. Confirm the QR belongs to the account above.</p>
                </div>
                <button type="submit" class="fm-btn primary">Save GCash settings</button>
            </form>
        @endif
    </div>
</section>
