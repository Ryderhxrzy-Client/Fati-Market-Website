@extends('layouts.admin-dashboard')

@section('title', 'Settings')

@section('content')
<div class="space-y-6">
    <!-- General Settings -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">General Settings</h3>
            <p class="text-sm text-gray-600">Manage your application preferences</p>
        </div>
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-gray-900">Dark Mode</h4>
                    <p class="text-sm text-gray-600">Enable dark theme for the admin panel</p>
                </div>
                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition" onclick="toggleDarkMode(this)">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition translate-x-1"></span>
                </button>
            </div>

            <div class="border-t border-gray-200 pt-6 flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-gray-900">Email Notifications</h4>
                    <p class="text-sm text-gray-600">Receive notifications about important events</p>
                </div>
                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-green-500 transition">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition translate-x-6"></span>
                </button>
            </div>

            <div class="border-t border-gray-200 pt-6 flex items-center justify-between">
                <div>
                    <h4 class="font-semibold text-gray-900">Desktop Notifications</h4>
                    <p class="text-sm text-gray-600">Get browser notifications in real-time</p>
                </div>
                <button class="relative inline-flex h-6 w-11 items-center rounded-full bg-gray-200 transition">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition translate-x-1"></span>
                </button>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Timezone</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="utc">UTC</option>
                    <option value="est">Eastern Time (EST)</option>
                    <option value="cst">Central Time (CST)</option>
                    <option value="mst">Mountain Time (MST)</option>
                    <option value="pst">Pacific Time (PST)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Display Settings -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">Display Settings</h3>
            <p class="text-sm text-gray-600">Customize how the interface appears</p>
        </div>
        <div class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Items Per Page</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="10">10 items</option>
                    <option value="25">25 items</option>
                    <option value="50">50 items</option>
                    <option value="100">100 items</option>
                </select>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Date Format</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="mdy">MM/DD/YYYY</option>
                    <option value="dmy">DD/MM/YYYY</option>
                    <option value="ymd">YYYY/MM/DD</option>
                </select>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Language</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="en">English</option>
                    <option value="es">Spanish</option>
                    <option value="fr">French</option>
                    <option value="de">German</option>
                </select>
            </div>
        </div>
    </div>

    <!-- API Settings -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-gray-900">API Configuration</h3>
            <p class="text-sm text-gray-600">Manage API settings and keys</p>
        </div>
        <div class="p-6 space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">API Endpoint</label>
                <div class="flex gap-2">
                    <input type="text" value="https://fati-api.alertaraqc.com/api" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">API Version</label>
                <input type="text" value="v1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 bg-gray-50">
            </div>

            <div class="border-t border-gray-200 pt-6">
                <h4 class="font-semibold text-gray-900 mb-3">API Keys</h4>
                <p class="text-sm text-gray-600 mb-4">Your API keys are used for authentication. Keep them secure.</p>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Public Key</p>
                            <p class="text-xs text-gray-500 mt-1">pk_live_abc123...</p>
                        </div>
                        <button class="px-3 py-1 text-red-600 hover:bg-red-50 rounded text-sm">
                            Revoke
                        </button>
                    </div>
                </div>
                <button class="mt-4 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Generate New Key
                </button>
            </div>
        </div>
    </div>

    <!-- Danger Zone -->
    <div class="bg-white rounded-lg shadow overflow-hidden border-2 border-red-200">
        <div class="px-6 py-4 border-b border-red-200 bg-red-50">
            <h3 class="text-lg font-bold text-red-900">Danger Zone</h3>
            <p class="text-sm text-red-800">Irreversible actions - proceed with caution</p>
        </div>
        <div class="p-6 space-y-4">
            <button class="w-full px-4 py-3 border-2 border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition font-medium flex items-center justify-between">
                <span>Reset All Settings</span>
                <i class="fas fa-refresh"></i>
            </button>
            <button class="w-full px-4 py-3 border-2 border-red-200 text-red-600 rounded-lg hover:bg-red-50 transition font-medium flex items-center justify-between">
                <span>Clear Cache</span>
                <i class="fas fa-trash"></i>
            </button>
            <button class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-medium flex items-center justify-between">
                <span>Delete Admin Account</span>
                <i class="fas fa-exclamation-triangle"></i>
            </button>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex gap-3 justify-end">
        <button class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium text-gray-700">
            Cancel
        </button>
        <button class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
            <i class="fas fa-save mr-2"></i>Save Changes
        </button>
    </div>
</div>

@push('scripts')
<script>
    function toggleDarkMode(button) {
        button.classList.toggle('bg-gray-200');
        button.classList.toggle('bg-green-500');
        const span = button.querySelector('span');
        span.classList.toggle('translate-x-1');
        span.classList.toggle('translate-x-6');
    }
</script>
@endpush
@endsection
