<x-app-layout :title="__('Dashboard')">
    <div class="container mx-auto px-4 m-4">
        <div class="flex gap-4 h-screen">
            <div class="w-1/3">
                @livewire('telegram-user-list')
            </div>
            <div class="w-2/3">
                @livewire('chat-conversation')
            </div>
        </div>
    </div>
</x-app-layout>