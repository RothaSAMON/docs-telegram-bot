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
                                {{ $user->first_name ?: 'Unknown' }} {{ $user->last_name ?: '' }} <svg class="inline-block w-4 h-4 mb-0.5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </p>
                            <p class="text-sm text-gray-500 truncate">
                                @if($user->username)
                                    {{ '@'.$user->username }}
                                @else
                                    unknown
                                @endif
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