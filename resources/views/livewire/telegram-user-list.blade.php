<div class="bg-white rounded-lg shadow-lg md:h-screen md:max-w-md">
    <div class="p-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Telegram Users</h2>
    </div>

    <div class="overflow-y-auto md:h-[calc(100vh-5rem)] h-[150px]">
        {{-- Block of each user --}}
        <ul class="divide-y">
            @forelse ($users as $user)
                <li wire:key="{{ $user->id }}" 
                    wire:click="selectUser({{ $user->id }})"
                    class="p-4 hover:bg-gray-50 cursor-pointer {{ $selectedUserId === $user->id ? 'bg-blue-50' : '' }}">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                                <span
                                    class="text-white text-lg font-semibold">{{ substr($user->first_name, 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">
                                {{ $user->first_name }} {{ $user->last_name }}
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                {{ '@' . $user->username }}
                            </p>
                            @if ($user->lastMessage)
                                <p class="text-xs text-gray-400 mt-1 truncate">
                                    {{ Str::limit($user->lastMessage->content, 30) }}
                                </p>
                            @endif
                        </div>
                        @if ($user->unread_count > 0)
                            <div class="flex-shrink-0">
                                <span
                                    class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-blue-500 rounded-full">
                                    {{ $user->unread_count }}
                                </span>
                            </div>
                        @endif
                    </div>
                </li>
            @empty
                <li class="p-4 text-center text-gray-500">
                    No users found.
                </li>
            @endforelse
        </ul>
    </div>
</div>