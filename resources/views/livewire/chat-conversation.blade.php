<div class="bg-white rounded-lg shadow-lg h-screen" wire:poll.10s>
    @if ($telegramUser)
        <!-- Chat Header -->
        <div class="p-4 border-b flex items-center space-x-3">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-semibold">{{ substr($telegramUser->first_name, 0, 1) }}</span>
                </div>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    {{ $telegramUser->first_name }} {{ $telegramUser->last_name }}
                </h3>
                <p class="text-sm text-gray-500">
                    {{ '@' . $telegramUser->username }}
                </p>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="h-[calc(100vh-10rem)] overflow-y-auto p-4 space-y-4" id="chat-messages" wire:key="messages-container">
            @foreach ($messages as $message)
                <div class="flex {{ $message['sender'] == 'admin' ? 'justify-end' : 'justify-start' }}"
                    wire:key="message-{{ $loop->index }}">
                    <div
                        class="max-w-[70%] {{ $message['sender'] == 'admin' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900' }} rounded-lg px-4 py-2 shadow">
                        <p class="text-sm">{{ $message['message'] }}</p>
                        <p
                            class="text-xs {{ $message['sender'] == 'admin' ? 'text-blue-100' : 'text-gray-500' }} mt-1">
                            {{ $message['created_at']->format('h:i A') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Chat Input -->
        <div class="p-4 border-t">
            <form wire:submit.prevent="sendMessage" class="flex space-x-2">
                <input type="text" wire:model="newMessage"
                    class="flex-1 rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Type your message..." autocomplete="off" />
                <button type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Send
                </button>
            </form>
        </div>
    @else
        <div class="h-full flex items-center justify-center">
            <p class="text-gray-500">Select a user to start chatting</p>
        </div>
    @endif
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Scroll to bottom when messages are loaded
            Livewire.on('conversationLoaded', () => {
                const messagesContainer = document.getElementById('chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });

            // Scroll to bottom when new message arrives
            Livewire.on('messageReceived', () => {
                const messagesContainer = document.getElementById('chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
        });
    </script>
@endpush