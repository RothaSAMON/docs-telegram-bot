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
                'type' => isset($messageData['photo']) ? 'photo' : 'text',
                'hasText' => isset($messageData['text']),
                'hasPhoto' => isset($messageData['photo'])
            ]);

            // Handle photo messages
            if (isset($messageData['photo'])) {
                // Get the largest photo (last in the array)
                $photo = end($messageData['photo']);
                $fileId = $photo['file_id'];
                
                Log::info('Processing photo', [
                    'fileId' => $fileId,
                    'size' => $photo['file_size']
                ]);
                
                $photoContent = $this->telegramService->getPhoto($fileId);
                if (!$photoContent) {
                    Log::error('Failed to get photo content from Telegram');
                    return null;
                }

                // Convert binary data to base64 for S3 upload
                $base64Content = base64_encode($photoContent);
                
                Log::info('Photo content retrieved', [
                    'contentLength' => strlen($base64Content),
                    'isBase64' => base64_encode(base64_decode($base64Content, true)) === $base64Content
                ]);
                
                $fileUrl = $this->s3FileUpload->uploadFile($base64Content);
                
                Log::info('Photo upload attempt', [
                    'success' => !empty($fileUrl),
                    'url' => $fileUrl ?? 'null'
                ]);

                if (!$fileUrl) {
                    Log::error('Failed to upload photo to S3');
                    return null;
                }

                try {
                    $messageData = [
                        'telegram_user_id' => $telegramUserId,
                        'content' => $messageData['caption'] ?? '',
                        'file_url' => $fileUrl,
                        'file_type' => 'photo',
                        'from_admin' => false,
                        'is_read' => false,
                    ];

                    Log::info('Attempting to create message with data', $messageData);

                    $message = TelegramMessage::create($messageData);

                    Log::info('Message created successfully', [
                        'message_id' => $message->id,
                        'file_url' => $message->file_url,
                        'file_type' => $message->file_type,
                        'telegram_user_id' => $message->telegram_user_id
                    ]);

                    return $message;
                } catch (\Exception $e) {
                    Log::error('Failed to create message record', [
                        'error' => $e->getMessage(),
                        'data' => $messageData
                    ]);
                    throw $e;
                }
            }
    
            // Handle text messages
            if (isset($messageData['text'])) {
              try {
                  $message = TelegramMessage::create([
                      'telegram_user_id' => $telegramUserId,
                      'content' => $messageData['text'],
                      'from_admin' => false,
                      'is_read' => false,
                  ]);
  
                  Log::info('Text message created successfully', [
                      'message_id' => $message->id,
                      'telegram_user_id' => $telegramUserId
                  ]);
  
                  return $message;
              } catch (\Exception $e) {
                  Log::error('Failed to create text message record', [
                      'error' => $e->getMessage(),
                      'telegram_user_id' => $telegramUserId
                  ]);
                  throw $e;
              }
          }
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