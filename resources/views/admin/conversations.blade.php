@extends('layouts.admin-dashboard')

@section('title', 'Conversations')

@section('content')
<div class="space-y-6">
    <!-- Header with Actions -->
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-lg font-bold text-gray-900">Conversations</h3>
            <p class="text-sm text-gray-600">Monitor and manage user conversations</p>
        </div>
        <div class="flex gap-3">
            <input type="search" placeholder="Search conversations..." id="searchInput" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
        </div>
    </div>

    <!-- Conversations List -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Conversations Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="divide-y divide-gray-200 max-h-screen overflow-y-auto">
                    <div class="p-4 hover:bg-gray-50 cursor-pointer transition conversation-item active" data-conversation-id="1">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold">
                                AJ
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">Alice Johnson</p>
                                <p class="text-xs text-gray-500 truncate">Last message preview...</p>
                            </div>
                            <span class="inline-flex items-center justify-center w-5 h-5 bg-red-500 text-white text-xs rounded-full">2</span>
                        </div>
                    </div>

                    <div class="p-4 hover:bg-gray-50 cursor-pointer transition conversation-item" data-conversation-id="2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-bold">
                                BS
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">Bob Smith</p>
                                <p class="text-xs text-gray-500 truncate">You: Thanks for the message</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 hover:bg-gray-50 cursor-pointer transition conversation-item" data-conversation-id="3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-purple-500 text-white flex items-center justify-center font-bold">
                                CD
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">Carol Davis</p>
                                <p class="text-xs text-gray-500 truncate">Interested in your item</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow flex flex-col h-screen max-h-screen">
                <!-- Chat Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center font-bold">
                            AJ
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Alice Johnson</p>
                            <p class="text-xs text-gray-500">Online</p>
                        </div>
                    </div>
                    <button class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-ellipsis-v text-lg"></i>
                    </button>
                </div>

                <!-- Messages Area -->
                <div class="flex-1 overflow-y-auto p-6 space-y-4">
                    <!-- Message from user -->
                    <div class="flex justify-start">
                        <div class="bg-gray-100 rounded-lg p-4 max-w-xs">
                            <p class="text-sm text-gray-900">Hi, is this item still available?</p>
                            <p class="text-xs text-gray-500 mt-1">10:30 AM</p>
                        </div>
                    </div>

                    <!-- Message from admin -->
                    <div class="flex justify-end">
                        <div class="bg-green-500 text-white rounded-lg p-4 max-w-xs">
                            <p class="text-sm">Yes, it's still available. Would you like to reserve it?</p>
                            <p class="text-xs text-green-100 mt-1">10:32 AM</p>
                        </div>
                    </div>

                    <!-- Message from user -->
                    <div class="flex justify-start">
                        <div class="bg-gray-100 rounded-lg p-4 max-w-xs">
                            <p class="text-sm text-gray-900">How much for shipping?</p>
                            <p class="text-xs text-gray-500 mt-1">10:35 AM</p>
                        </div>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex gap-3">
                        <input type="text" placeholder="Type a message..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('searchInput').addEventListener('keyup', function(e) {
        const searchTerm = e.target.value.toLowerCase();
        document.querySelectorAll('.conversation-item').forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function() {
            document.querySelectorAll('.conversation-item').forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            console.log('Selected conversation:', this.dataset.conversationId);
        });
    });
</script>
@endpush
@endsection
