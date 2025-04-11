<?php

namespace App\Livewire;

use App\Events\MessageSent;
use Livewire\Component;
use App\Models\TelegramUser;
use App\Models\TelegramMessage;
use App\Services\TelegramService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use App\Services\S3FileUpload;
use Livewire\WithFileUploads;

class ChatConversation extends Component
{
    use WithFileUploads;
    
    public ?TelegramUser $telegramUser = null;
    public string $newMessage = '';
    public Collection $messages;
    public $uploadedFile;
    public $uploadedDocument;

    #[On('echo:telegram-messages,.MessageReceived')] 
    public function handleNewMessage($event)
    {
        Log::info('Received new message event', ['event' => $event]);
        
        if ($this->telegramUser && isset($event['telegram_user_id']) && $event['telegram_user_id'] == $this->telegramUser->id) {
            $message = TelegramMessage::where('telegram_user_id', $event['telegram_user_id'])
                ->latest()
                ->first();

            if ($message) {
                $messageData = [
                    'sender' => $message->from_admin ? 'admin' : 'user',
                    'message' => $message->content,
                    'file_url' => $message->file_url,
                    'file_type' => $message->file_type,
                    'media_group_id' => $message->media_group_id,
                    'created_at' => $message->created_at
                ];

                // If it's part of a media group, get all related messages
                if ($message->media_group_id) {
                    $groupMessages = TelegramMessage::where('media_group_id', $message->media_group_id)->get();
                    $messageData['group_files'] = $groupMessages->map(function ($groupMsg) {
                        return [
                            'file_url' => $groupMsg->file_url,
                            'file_type' => $groupMsg->file_type
                        ];
                    })->toArray();
                }

                $this->messages->push($messageData);
                $this->dispatch('messageReceived');
            }
        }
    }

    public function getListeners()
    {
        return [
            'echo:telegram-messages,.MessageReceived' => 'handleNewMessage',
            'conversationSelected' => 'loadConversation',
            'echo-private:telegram.user.' . ($this->telegramUser?->id ?? '') . ',MessageSent' => 'handleNewMessage'
        ];
    }

    public function mount()
    {
        $this->messages = collect([]);
    }

    #[On('conversationSelected')]
    public function loadConversation($userId)
    {
        Log::info('Loading conversation for user', ['user_id' => $userId]);

        $this->telegramUser = TelegramUser::find($userId);
        if (!$this->telegramUser) {
            Log::error('User not found', ['user_id' => $userId]);
            return;
        }

        // Get all messages and group them properly
        $messages = TelegramMessage::where('telegram_user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->get();

        $processedGroups = [];
        $this->messages = collect();

        foreach ($messages as $message) {
            // Skip if this message is part of an already processed group
            if ($message->media_group_id && in_array($message->media_group_id, $processedGroups)) {
                continue;
            }

            $messageData = [
                'sender' => $message->from_admin ? 'admin' : 'user',
                'message' => $message->content,
                'file_url' => $message->file_url,
                'file_type' => $message->file_type,
                'media_group_id' => $message->media_group_id,
                'created_at' => $message->created_at
            ];

            // If it's part of a media group, collect all related messages
            if ($message->media_group_id) {
                $groupMessages = $messages->where('media_group_id', $message->media_group_id);
                $messageData['group_files'] = $groupMessages->map(function ($groupMsg) {
                    return [
                        'file_url' => $groupMsg->file_url,
                        'file_type' => $groupMsg->file_type,
                        'created_at' => $groupMsg->created_at
                    ];
                })->values()->toArray();
                $processedGroups[] = $message->media_group_id;
            }

            // In handleNewMessage method
            if ($message->media_group_id) {
                $groupMessages = TelegramMessage::where('media_group_id', $message->media_group_id)
                    ->orderBy('created_at', 'asc')
                    ->get();
                $messageData['group_files'] = $groupMessages->map(function ($groupMsg) {
                    return [
                        'file_url' => $groupMsg->file_url,
                        'file_type' => $groupMsg->file_type,
                        'created_at' => $groupMsg->created_at
                    ];
                })->values()->toArray();
            }

            $this->messages->push($messageData);
        }

        Log::info('Conversation loaded', [
            'user_id' => $userId,
            'message_count' => $this->messages->count(),
            'media_groups' => $processedGroups
        ]);
    }

    public function updatedUploadedFile()
    {
        Log::info('File uploaded to component', [
            'fileName' => $this->uploadedFile->getClientOriginalName(),
            'fileSize' => $this->uploadedFile->getSize(),
            'mimeType' => $this->uploadedFile->getMimeType()
        ]);

        $this->sendFileMessage();
    }

    public function sendFileMessage()
    {
        if (!$this->uploadedFile || !$this->telegramUser) {
            Log::info('File upload validation', [
                'hasFile' => (bool)$this->uploadedFile,
                'hasUser' => (bool)$this->telegramUser,
                'fileData' => $this->uploadedFile ? [
                    'name' => $this->uploadedFile->getClientOriginalName(),
                    'size' => $this->uploadedFile->getSize()
                ] : null
            ]);
            return;
        }

        try {
            // Get file contents
            $fileContents = $this->uploadedFile->get();
            Log::info('File contents retrieved', [
                'size' => strlen($fileContents)
            ]);

            // Upload to S3
            $s3Service = app(S3FileUpload::class);
            $fileUrl = $s3Service->uploadFile(
                base64_encode($fileContents),
                $this->uploadedFile->getClientOriginalExtension() ?: 'jpg'
            );

            Log::info('S3 upload result', [
                'success' => (bool)$fileUrl,
                'url' => $fileUrl
            ]);

            if (!$fileUrl) {
                throw new \Exception('Failed to upload file to S3');
            }

            // Create database record
            $message = TelegramMessage::create([
                'telegram_user_id' => $this->telegramUser->id,
                'content' => '',
                'file_url' => $fileUrl,
                'file_type' => 'photo',
                'from_admin' => true,
                'is_read' => true,
            ]);

            Log::info('Database record created', [
                'messageId' => $message->id,
                'data' => $message->toArray()
            ]);

            // Send to Telegram
            $telegramService = app(TelegramService::class);
            $sent = $telegramService->sendPhoto(
                $this->telegramUser->chat_id,
                $fileUrl
            );

            Log::info('Telegram send result', [
                'success' => $sent,
                'chatId' => $this->telegramUser->chat_id
            ]);

            if ($sent) {
                $this->messages->push([
                    'sender' => 'admin',
                    'message' => '',
                    'file_url' => $fileUrl,
                    'file_type' => 'photo',
                    'created_at' => now()
                ]);

                $this->uploadedFile = null;
                $this->dispatch('messageReceived');
                $this->dispatch('messageReceived')->to('telegram-user-list');
            }
        } catch (\Exception $e) {
            Log::error('Error in sendFileMessage', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function updatedUploadedDocument()
    {
        Log::info('Document uploaded to component', [
            'fileName' => $this->uploadedDocument->getClientOriginalName(),
            'fileSize' => $this->uploadedDocument->getSize(),
            'mimeType' => $this->uploadedDocument->getMimeType()
        ]);

        $this->sendDocumentMessage();
    }

    public function sendDocumentMessage()
    {
        if (!$this->uploadedDocument || !$this->telegramUser) {
            return;
        }

        try {
            $fileContents = $this->uploadedDocument->get();
            $s3Service = app(S3FileUpload::class);
            $fileUrl = $s3Service->uploadFile(
                base64_encode($fileContents),
                $this->uploadedDocument->getClientOriginalExtension()
            );

            if (!$fileUrl) {
                throw new \Exception('Failed to upload document to S3');
            }

            $message = TelegramMessage::create([
                'telegram_user_id' => $this->telegramUser->id,
                'content' => '',
                'file_url' => $fileUrl,
                'file_type' => 'document',
                'filename' => $this->uploadedDocument->getClientOriginalName(),
                'from_admin' => true,
                'is_read' => true,
            ]);

            $telegramService = app(TelegramService::class);
            $sent = $telegramService->sendDocument(
                $this->telegramUser->chat_id,
                $fileUrl,
                $this->uploadedDocument->getClientOriginalName()
            );

            if ($sent) {
                $messageData = [
                    'sender' => 'admin',
                    'message' => '',
                    'file_url' => $fileUrl,
                    'file_type' => 'document',
                    'filename' => $this->uploadedDocument->getClientOriginalName(),
                    'created_at' => now()
                ];

                $this->messages->push($messageData);
                $this->uploadedDocument = null;
                
                // Broadcast the message
                broadcast(new MessageSent($message))->toOthers();
                
                $this->dispatch('messageReceived');
                $this->dispatch('messageReceived')->to('telegram-user-list');
            }
        } catch (\Exception $e) {
            Log::error('Error sending document message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function sendMessage()
    {
        if (empty($this->newMessage) || !$this->telegramUser) {
            Log::info('Message validation failed', [
                'hasMessage' => !empty($this->newMessage),
                'hasUser' => (bool)$this->telegramUser
            ]);
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

            Log::info('Database record created', [
                'messageId' => $message->id,
                'content' => $this->newMessage
            ]);

            // Send via Telegram
            $sent = app(TelegramService::class)->sendMessage(
                $this->telegramUser->chat_id,
                $this->newMessage
            );

            Log::info('Telegram send result', [
                'success' => $sent,
                'chatId' => $this->telegramUser->chat_id
            ]);

            if ($sent) {
                // Add to local messages
                $this->messages->push([
                    'sender' => 'admin',
                    'message' => $this->newMessage,
                    'created_at' => now()
                ]);

                $this->newMessage = '';
                $this->dispatch('messageReceived');
                $this->dispatch('messageReceived')->to('telegram-user-list');
            }
        } catch (\Exception $e) {
            Log::error('Error sending message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}