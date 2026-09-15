@extends('layouts.admin-dashboard')

@section('title', 'Settings')
@section('subtitle', 'What the store tells buyers and sellers')

@section('content')
<div class="space-y-6">
    {{--
        Only settings that exist. This page used to carry a dark-mode switch
        wired to nothing, timezone and language pickers that saved nowhere,
        API keys that did not exist and a "Delete admin account" button. The
        three groups below are the ones the mobile app has, from the same
        endpoints.
    --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 items-start">
        <div class="space-y-6">
            <div id="store-hours">
                @include('admin.partials.store-hours-settings')
            </div>
            <div id="gcash">
                @include('admin.partials.gcash-settings')
            </div>
        </div>

        <div class="space-y-6">
            <div id="location">
                @include('admin.partials.store-location')
            </div>

            <section class="fm-card" aria-labelledby="about-heading">
                <div class="fm-card-head">
                    <h4 id="about-heading">About this console</h4>
                </div>
                <div class="fm-card-body space-y-3" style="font-size: 13.5px;">
                    <div class="flex justify-between gap-4">
                        <span style="color: var(--ink-500);">Store</span>
                        <span class="font-medium">Ofelia's Store &middot; Fati Market</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span style="color: var(--ink-500);">Institution</span>
                        <span class="font-medium">Our Lady of Fatima University</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span style="color: var(--ink-500);">Signed in as</span>
                        <span class="font-medium">{{ session('admin_data.email', 'Administrator') }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span style="color: var(--ink-500);">API</span>
                        <span class="font-medium" style="font-family: ui-monospace, monospace; font-size: 12px;">fati-api.alertaraqc.com</span>
                    </div>
                    <p style="color: var(--ink-500); font-size: 12.5px; margin: 0; padding-top: 6px; border-top: 1px solid var(--line);">
                        Every change here is live for the mobile app the moment it is saved.
                    </p>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
