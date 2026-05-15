@extends('layouts.admin-dashboard')

@section('title', 'Admin Profile')

@section('content')
<div class="space-y-6">
    <!-- Profile Header -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="h-32 bg-gradient-to-r from-green-500 to-blue-500"></div>
        <div class="px-6 pb-6">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between -mt-16 mb-4">
                <div class="flex items-end gap-4 mb-4 md:mb-0">
                    <div class="w-32 h-32 rounded-full bg-gradient-to-br from-green-400 to-blue-500 border-4 border-white shadow-lg"></div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $adminData['name'] ?? 'Admin User' }}</h2>
                        <p class="text-gray-600">Administrator</p>
                        <p class="text-sm text-gray-500 mt-1">Member since {{ $adminData['created_at'] ?? 'N/A' }}</p>
                    </div>
                </div>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    <i class="fas fa-camera mr-2"></i>Change Photo
                </button>
            </div>
        </div>
    </div>

    <!-- Profile Info and Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Information -->
        <div class="md:col-span-2 bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6">Profile Information</h3>
            <form class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                        <input type="text" value="{{ $adminData['first_name'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <input type="text" value="{{ $adminData['last_name'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" value="{{ $adminData['email'] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input type="tel" value="{{ $adminData['phone'] ?? '' }}" placeholder="+1 (555) 000-0000" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                    <textarea rows="3" placeholder="Tell us about yourself..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>

                <div class="pt-4 border-t border-gray-200 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-medium">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <button type="button" class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium text-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>

        <!-- Stats Sidebar -->
        <div class="space-y-6">
            <!-- Wallet/Points -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Platform Points</h3>
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-600 mb-2">2,450</div>
                    <p class="text-gray-600 text-sm">Total points earned</p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-xs text-gray-500 mb-2">Point Breakdown</p>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-700">Transactions</span>
                            <span class="font-semibold text-gray-900">1,200</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700">Referrals</span>
                            <span class="font-semibold text-gray-900">950</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-700">Bonuses</span>
                            <span class="font-semibold text-gray-900">300</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Info</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <p class="text-gray-600">Email Verified</p>
                        <p class="text-green-600 font-semibold flex items-center gap-2 mt-1">
                            <i class="fas fa-check-circle"></i>Yes
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Two-Factor Auth</p>
                        <p class="text-red-600 font-semibold flex items-center gap-2 mt-1">
                            <i class="fas fa-times-circle"></i>Not Enabled
                        </p>
                    </div>
                    <div>
                        <p class="text-gray-600">Last Login</p>
                        <p class="text-gray-900 font-semibold mt-1">Today at 10:30 AM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Section -->
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-900 mb-6">Security Settings</h3>
        <div class="space-y-4">
            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div>
                    <h4 class="font-semibold text-gray-900">Change Password</h4>
                    <p class="text-sm text-gray-600">Update your password regularly</p>
                </div>
                <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                    Change
                </button>
            </div>

            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div>
                    <h4 class="font-semibold text-gray-900">Two-Factor Authentication</h4>
                    <p class="text-sm text-gray-600">Enhance your account security</p>
                </div>
                <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm font-medium">
                    Enable
                </button>
            </div>

            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                <div>
                    <h4 class="font-semibold text-gray-900">Login Sessions</h4>
                    <p class="text-sm text-gray-600">Manage active sessions</p>
                </div>
                <button class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                    View
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
