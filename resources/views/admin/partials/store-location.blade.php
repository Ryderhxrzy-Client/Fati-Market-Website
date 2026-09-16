@php
    // The same fixed pin the mobile app shows. The store does not move, so
    // the address lives here rather than behind a request.
    $storeName = "Ofelia's Store";
    $storeAddress = 'Hollywood Terraces, Sumulong Hwy, Antipolo, 1870, Rizal';
    $storeLat = '14.619292';
    $storeLng = '121.151418';
    $embedUrl = "https://maps.google.com/maps?q={$storeLat},{$storeLng}&z=16&output=embed";
    $mapsUrl = "https://www.google.com/maps/search/?api=1&query={$storeLat},{$storeLng}";
    $directionsUrl = "https://www.google.com/maps/dir/?api=1&destination={$storeLat},{$storeLng}";
@endphp

<section class="fm-card" aria-labelledby="location-heading">
    <div class="fm-card-head">
        <div>
            <h4 id="location-heading">Store location</h4>
            <p class="cell-sub" style="margin-top: 2px;">Where every meet-up and walk-in pickup happens. Students see this on their profile.</p>
        </div>
    </div>

    <div style="position: relative; height: 260px; background: var(--surface-sunk);">
        <iframe
            title="Map of {{ $storeName }}"
            src="{{ $embedUrl }}"
            style="border: 0; width: 100%; height: 100%; display: block;"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>

    <div class="fm-card-body">
        <div class="flex items-start gap-3">
            <div class="stat-icon" style="background: var(--brand-100); color: var(--brand-700);">
                <i class="fas fa-location-dot"></i>
            </div>
            <div class="min-w-0 flex-1">
                <p class="cell-title" style="margin: 0;">{{ $storeName }}</p>
                <p style="margin: 2px 0 0; font-size: 13px; color: var(--ink-600);" id="storeAddressText">{{ $storeAddress }}</p>
                <p class="cell-sub" style="margin-top: 2px;">{{ $storeLat }}, {{ $storeLng }}</p>
            </div>
            <button type="button" class="row-btn" title="Copy address" onclick="copyStoreAddress()">
                <i class="fas fa-copy"></i>
            </button>
        </div>

        <div class="flex flex-wrap gap-2 mt-4">
            <a href="{{ $directionsUrl }}" target="_blank" rel="noopener" class="fm-btn primary">
                <i class="fas fa-diamond-turn-right"></i>Directions
            </a>
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener" class="fm-btn ghost">
                <i class="fas fa-map"></i>Open in Google Maps
            </a>
        </div>
    </div>
</section>

@push('scripts')
<script>
    function copyStoreAddress() {
        const text = document.getElementById('storeAddressText').textContent.trim();
        const done = () => { if (typeof showToast === 'function') showToast('Address copied', 'success'); };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done).catch(() => window.prompt('Copy the address:', text));
        } else {
            window.prompt('Copy the address:', text);
        }
    }
</script>
@endpush
