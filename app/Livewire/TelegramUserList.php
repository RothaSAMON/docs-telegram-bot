<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TelegramUser;
use App\Models\TelegramMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class TelegramUserList extends Component
{
    public Collection $users;
    public $selectedUserId = null;

    protected $listeners = [
        'echo:users,.user.added' => 'handleUserAdded',
        'echo:telegram-messages,.MessageReceived' => 'handleNewMessage'
    ];

    public function mount()
    {
        $this->users = collect([]);
        $this->refreshUsers();
    }

    public function refreshUsers()
    {
        // $this->users = TelegramUser::with(['lastMessage'])
        //     ->latest()
        //     ->get()
        //     ->map(function ($user) {
        //         $user->unread_count = TelegramMessage::where('telegram_user_id', $user->id)
        //             ->where('is_read', false)
        //             ->count();
        //         return $user;
        //     });
        $this->users = TelegramUser::with(['lastMessage'])
        ->get()
        ->map(function ($user) {
            $user->unread_count = TelegramMessage::where('telegram_user_id', $user->id)
                ->where('is_read', false)
                ->count();
            return $user;
        })
        ->sortByDesc(function ($user) {
            return $user->lastMessage?->created_at ?? $user->created_at;
        })
        ->values();
    }

    public function selectUser($userId)
    {
        Log::info('User selected', ['user_id' => $userId]);
        $this->selectedUserId = $userId;

        // Mark messages as read
        TelegramMessage::where('telegram_user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Dispatch event to load conversation
        $this->dispatch('conversationSelected', userId: $userId)->to('chat-conversation');

        $this->refreshUsers(); // Update unread counts
    }

    #[On('messageReceived')]
    public function handleMessageReceived()
    {
        $this->refreshUsers();
    }

    public function handleUserAdded($event)
    {
        Log::info('User added event received', $event);
        $this->refreshUsers();
    }

    public function handleNewMessage($event)
    {
        Log::info('New message event received', $event);
        $this->refreshUsers();
    }

    public function render()
    {
        return view('livewire.telegram-user-list');
    }
}