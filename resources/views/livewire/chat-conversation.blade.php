<div class="bg-white rounded-lg shadow-lg h-screen">
    @if ($telegramUser)
    <div x-data x-init="$nextTick(() => {
        const messageContainer = document.getElementById('chat-messages');
        if (messageContainer) {
            messageContainer.scrollTop = messageContainer.scrollHeight;
        }
    })">
        <!-- Chat Header -->
        <div class="p-4 border-b flex items-center space-x-3">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                    <span class="text-white font-semibold">{{ substr($telegramUser->first_name, 0, 1) }}</span>
                </div>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    {{ $telegramUser->first_name }} {{ $telegramUser->last_name }} <svg class="inline-block w-4 h-4 mb-1 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </h3>
                <p class="text-sm text-gray-500">
                    {{ '@' . $telegramUser->username }}
                </p>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="h-[calc(100vh-10rem)] overflow-y-auto p-4 space-y-4" id="chat-messages" wire:key="messages-container">
            @php
                $processedGroups = [];
            @endphp
            
            @foreach ($messages as $message)
                @if (
                    !isset($message['media_group_id']) || 
                    !in_array($message['media_group_id'], $processedGroups)
                )
                    <div class="flex {{ $message['sender'] == 'admin' ? 'justify-end' : 'justify-start' }}"
                        wire:key="message-{{ $loop->index }}">
                        <div class="max-w-[70%] {{ $message['sender'] == 'admin' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-900' }} rounded-lg px-2 py-2 shadow">
                            @if (isset($message['media_group_id']) && $message['file_type'] === 'photo')
                                <div class="grid grid-cols-2 gap-1 mb-2">
                                    @foreach ($messages->where('media_group_id', $message['media_group_id']) as $groupedMessage)
                                        @if (isset($groupedMessage['file_url']) && $groupedMessage['file_type'] === 'photo')
                                            <div class="relative aspect-square">
                                                <img src="{{ $groupedMessage['file_url'] }}" 
                                                    alt="Shared image" 
                                                    class="rounded-lg w-full h-full object-cover cursor-pointer"
                                                    onclick="window.open('{{ $groupedMessage['file_url'] }}', '_blank')"
                                                    loading="lazy">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                @php
                                    $processedGroups[] = $message['media_group_id'];
                                @endphp
                            @elseif (isset($message['file_url']) && $message['file_type'] === 'photo' && !isset($message['media_group_id']))
                                <div class="">
                                    <img src="{{ $message['file_url'] }}" 
                                        alt="Shared image" 
                                        class="rounded-lg max-w-full md:max-w-[400px] h-auto cursor-pointer"
                                        onclick="window.open('{{ $message['file_url'] }}', '_blank')"
                                        loading="lazy">
                                </div>
                            @endif
                            @if (!empty($message['message']))
                                <p class="text-sm">{{ $message['message'] }}</p>
                            @endif
                            <p class="text-xs {{ $message['sender'] == 'admin' ? 'text-blue-100' : 'text-gray-500' }} mt-1">
                                {{ $message['created_at']->format('h:i A') }}
                            </p>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
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

<script>
    document.addEventListener('livewire:initialized', () => {
        const scrollToBottom = () => {
            const messageContainer = document.getElementById('chat-messages');
            if (messageContainer) {
                setTimeout(() => {
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                }, 200);
            }
        };

        // When conversation is loaded
        Livewire.on('conversationLoaded', () => {
            scrollToBottom();
        });

        // When message is sent or received
        Livewire.on('messageSent', scrollToBottom);
        Livewire.on('messageReceived', scrollToBottom);

        // Watch for changes in the messages container
        const observer = new MutationObserver(scrollToBottom);
        const messageContainer = document.getElementById('chat-messages');
        if (messageContainer) {
            observer.observe(messageContainer, { 
                childList: true,
                subtree: true 
            });
        }
    });
</script>