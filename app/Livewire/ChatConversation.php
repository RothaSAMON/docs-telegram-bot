<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TelegramUser;
use App\Models\TelegramMessage;
use App\Services\TelegramService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use App\Services\S3FileUpload;

class ChatConversation extends Component
{
    public ?TelegramUser $telegramUser = null;
    public string $newMessage = '';
    public Collection $messages;

    protected $listeners = [
        'echo:telegram-messages,.MessageReceived' => 'handleNewMessage'
    ];

    public function mount()
    {
        $this->messages = collect([]);
    }

    #[On('conversationSelected')]
    public function loadConversation($userId)
    {
        Log::info('Loading conversation for user', ['user_id' => $userId]);

        $this->telegramUser = TelegramUser::query()->find($userId);
        if (!$this->telegramUser) {
            Log::error('User not found', ['user_id' => $userId]);
            return;
        }

        $this->messages = TelegramMessage::where('telegram_user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'sender' => $message->from_admin ? 'admin' : 'user',
                    'message' => $message->content,
                    'file_url' => $message->file_url,
                    'file_type' => $message->file_type,
                    'media_group_id' => $message->media_group_id,
                    'created_at' => $message->created_at
                ];
            });

        Log::info('Conversation loaded', [
            'user_id' => $userId,
            'message_count' => $this->messages->count()
        ]);

        $this->dispatch('conversationLoaded');
    }

    public function sendMessage()
    {
        if (empty($this->newMessage) || !$this->telegramUser) {
            return;
        }

        try {
            // Create message in database
            $message = TelegramMessage::create([
                'telegram_user_id' => $this->telegramUser->id,
                'content' => $this->newMessage,
                'from_admin' => true,
                'is_read' => true,
            ]);

            // Send message via Telegram
            app(TelegramService::class)->sendMessage(
                $this->telegramUser->chat_id,
                $this->newMessage
            );

            // Add message to local collection
            $this->messages->push([
                'sender' => 'admin',
                'message' => $this->newMessage,
                'created_at' => now()
            ]);

            $this->newMessage = '';

            // Dispatch events
            $this->dispatch('messageReceived');
            $this->dispatch('messageReceived')->to('telegram-user-list');
        } catch (\Exception $e) {
            Log::error('Error sending message', [
                'error' => $e->getMessage(),
                'user_id' => $this->telegramUser->id
            ]);
        }
    }

    public function handleNewMessage($event)
    {
        Log::info('New message received in conversation', $event);
    
        if (!$this->telegramUser) {
            return;
        }
    
        if ($event['telegram_user_id'] === $this->telegramUser->id) {
            // Fetch the latest message from the database
            $latestMessage = TelegramMessage::where('telegram_user_id', $this->telegramUser->id)
                ->latest()
                ->first();
    
            if ($latestMessage) {
                $this->messages = TelegramMessage::where('telegram_user_id', $this->telegramUser->id)
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(function ($message) {
                        return [
                            'sender' => $message->from_admin ? 'admin' : 'user',
                            'message' => $message->content,
                            'file_url' => $message->file_url,
                            'file_type' => $message->file_type,
                            'media_group_id' => $message->media_group_id,
                            'created_at' => $message->created_at
                        ];
                    });
    
                $this->dispatch('messageReceived');
                $this->dispatch('messageReceived')->to('telegram-user-list');
            }
        }
    }

    public function render()
    {
        return view('livewire.chat-conversation');
    }

    public function sendVoiceMessage($audioBlob)
    {
        if (!$this->telegramUser) {
            return;
        }
    
        try {
            // Upload to S3
            $fileUrl = app(S3FileUpload::class)->uploadFile($audioBlob, 'voice');
    
            // Create message in database
            $message = TelegramMessage::create([
                'telegram_user_id' => $this->telegramUser->id,
                'content' => '',
                'file_url' => $fileUrl,
                'file_type' => 'voice',
                'from_admin' => true,
                'is_read' => true,
            ]);
    
            // Send via Telegram
            app(TelegramService::class)->sendVoice(
                $this->telegramUser->chat_id,
                $fileUrl
            );
    
            // Update local messages
            $this->messages->push([
                'sender' => 'admin',
                'message' => '',
                'file_url' => $fileUrl,
                'file_type' => 'voice',
                'created_at' => now()
            ]);
    
            $this->dispatch('messageReceived');
            $this->dispatch('messageReceived')->to('telegram-user-list');
        } catch (\Exception $e) {
            Log::error('Error sending voice message', [
                'error' => $e->getMessage(),
                'user_id' => $this->telegramUser->id
            ]);
        }
    }
}