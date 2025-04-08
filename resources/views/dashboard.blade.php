<x-app-layout :title="__('Dashboard')">
    <div class="container mx-auto px-4 m-4">
        <div class="block md:flex gap-4 h-screen space-y-4 md:space-y-0">
            <div class="md:w-1/3">
                @livewire('telegram-user-list')
            </div>
            <div class="md:w-2/3">
                @livewire('chat-conversation')
            </div>
        </div>
    </div>
</x-app-layout>