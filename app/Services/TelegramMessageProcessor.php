<?php

namespace App\Services;

use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Log;

class TelegramMessageProcessor
{
    protected TelegramService $telegramService;
    protected S3FileUpload $s3FileUpload;

    public function __construct(
        TelegramService $telegramService,
        S3FileUpload $s3FileUpload
    ) {
        $this->telegramService = $telegramService;
        $this->s3FileUpload = $s3FileUpload;
    }

    public function processMessage(array $messageData, int $telegramUserId): ?TelegramMessage
    {
        try {
            Log::info('Processing message data', [
                'messageData' => $messageData,
                'type' => isset($messageData['photo']) ? 'photo' : 'text',
                'hasText' => isset($messageData['text']),
                'hasPhoto' => isset($messageData['photo']),
                'hasVoice' => isset($messageData['voice']),
                'hasVideo' => isset($messageData['video']),
                'media_group_id'=> $messageData['media_group_id'] ?? null,
            ]);
    
            // Handle text messages first
            if (isset($messageData['text'])) {
                return TelegramMessage::create([
                    'telegram_user_id' => $telegramUserId,
                    'content' => $messageData['text'],
                    'from_admin' => false,
                    'is_read' => false,
                ]);
            }
    
            // Handle video messages
            if (isset($messageData['video'])) {
                $fileId = $messageData['video']['file_id'];
                $videoContent = $this->telegramService->getVideo($fileId);
                if (!$videoContent) {
                    Log::error('Failed to get video content from Telegram');
                    return null;
                }
    
                $base64Content = base64_encode($videoContent);
                $fileUrl = $this->s3FileUpload->uploadFile($base64Content, 'mp4');
                
                if (!$fileUrl) {
                    Log::error('Failed to upload video to S3');
                    return null;
                }
    
                return TelegramMessage::create([
                    'telegram_user_id' => $telegramUserId,
                    'content' => $messageData['caption'] ?? '',
                    'file_url' => $fileUrl,
                    'file_type' => 'video',
                    'from_admin' => false,
                    'is_read' => false,
                ]);
            }
    
            // Handle voice messages
            if (isset($messageData['voice'])) {
                $fileId = $messageData['voice']['file_id'];
                $voiceContent = $this->telegramService->getVoice($fileId);
                if (!$voiceContent) {
                    Log::error('Failed to get voice content from Telegram');
                    return null;
                }
    
                $base64Content = base64_encode($voiceContent);
                $fileUrl = $this->s3FileUpload->uploadFile($base64Content, 'ogg');
                
                if (!$fileUrl) {
                    Log::error('Failed to upload voice to S3');
                    return null;
                }
    
                return TelegramMessage::create([
                    'telegram_user_id' => $telegramUserId,
                    'content' => $messageData['caption'] ?? '',
                    'file_url' => $fileUrl,
                    'file_type' => 'voice',
                    'from_admin' => false,
                    'is_read' => false,
                ]);
            }
    
            // Handle photo messages
            if (isset($messageData['photo'])) {
                $photo = end($messageData['photo']);
                $fileId = $photo['file_id'];
                $photoContent = $this->telegramService->getPhoto($fileId);
                if (!$photoContent) {
                    Log::error('Failed to get photo content from Telegram');
                    return null;
                }
    
                $base64Content = base64_encode($photoContent);
                $fileUrl = $this->s3FileUpload->uploadFile($base64Content);
                
                if (!$fileUrl) {
                    Log::error('Failed to upload photo to S3');
                    return null;
                }
    
                return TelegramMessage::create([
                    'telegram_user_id' => $telegramUserId,
                    'content' => $messageData['caption'] ?? '',
                    'file_url' => $fileUrl,
                    'file_type' => 'photo',
                    'from_admin' => false,
                    'is_read' => false,
                ]);
            }
    
            // If no recognized message type was handled, log and return unsupported type
            Log::warning('Unhandled message type received', [
                'messageData' => $messageData
            ]);
            
            return TelegramMessage::create([
                'telegram_user_id' => $telegramUserId,
                'content' => '[Unsupported message type]',
                'from_admin' => false,
                'is_read' => false,
            ]);
    
        } catch (\Exception $e) {
            Log::error('Error processing message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'messageData' => $messageData
            ]);
            return null;
        }
    }
}