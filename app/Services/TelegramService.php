<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $apiUrl;
    protected string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $chatId, string $message): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Telegram message', [
                    'chat_id' => $chatId,
                    'error' => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function getPhoto(string $fileId): ?string
    {
        try {
            // Get file path from Telegram
            $response = Http::get("{$this->apiUrl}/getFile", [
                'file_id' => $fileId
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get file path from Telegram', [
                    'file_id' => $fileId,
                    'error' => $response->json(),
                ]);
                return null;
            }

            $filePath = $response->json()['result']['file_path'];
            
            // Download file content
            $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
            $fileContent = Http::get($fileUrl)->body();
            
            if (empty($fileContent)) {
                return null;
            }

            return $fileContent;
        } catch (\Exception $e) {
            Log::error('Error getting photo from Telegram', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getVoice(string $fileId): ?string
    {
        try {
            // Get file path from Telegram
            $response = Http::get("{$this->apiUrl}/getFile", [
                'file_id' => $fileId
            ]);

            if (!$response->successful()) {
                Log::error('Failed to get voice file path from Telegram', [
                    'file_id' => $fileId,
                    'error' => $response->json(),
                ]);
                return null;
            }

            $filePath = $response->json()['result']['file_path'];
            
            // Download file content
            $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
            $fileContent = Http::get($fileUrl)->body();
            
            if (empty($fileContent)) {
                return null;
            }

            return $fileContent;
        } catch (\Exception $e) {
            Log::error('Error getting voice file', [
                'error' => $e->getMessage(),
                'fileId' => $fileId
            ]);
            return null;
        }
    }
    
    public function sendVoice(string $chatId, string $voiceUrl): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendVoice", [
                'chat_id' => $chatId,
                'voice' => $voiceUrl
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send voice message', [
                    'chat_id' => $chatId,
                    'error' => $response->json(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending voice message', [
                'error' => $e->getMessage(),
                'chatId' => $chatId
            ]);
            return false;
        }
    }
    
    public function getVideo(string $fileId): ?string
    {
        try {
            $filePath = $this->getFilePath($fileId);
            if (!$filePath) {
                return null;
            }
            
            return $this->downloadFile($filePath);
        } catch (\Exception $e) {
            Log::error('Error getting video from Telegram', [
                'error' => $e->getMessage(),
                'fileId' => $fileId
            ]);
            return null;
        }
    }

    protected function getFilePath(string $fileId): ?string
    {
        $response = Http::get("{$this->apiUrl}/getFile", [
            'file_id' => $fileId
        ]);

        if (!$response->successful()) {
            Log::error('Failed to get file path from Telegram', [
                'file_id' => $fileId,
                'error' => $response->json(),
            ]);
            return null;
        }

        return $response->json()['result']['file_path'] ?? null;
    }

    protected function downloadFile(string $filePath): ?string
    {
        $fileUrl = "https://api.telegram.org/file/bot{$this->botToken}/{$filePath}";
        $fileContent = Http::get($fileUrl)->body();
        
        if (empty($fileContent)) {
            Log::error('Failed to download file from Telegram', [
                'file_path' => $filePath
            ]);
            return null;
        }

        return $fileContent;
    }

    public function sendPhoto(string $chatId, string $photoUrl): bool
    {
        try {
            Log::info('Attempting to send photo', [
                'chat_id' => $chatId,
                'photo_url' => $photoUrl
            ]);
    
            // Check if the photo URL is accessible
            $photoCheck = Http::get($photoUrl);
            if (!$photoCheck->successful()) {
                Log::error('Photo URL is not accessible', [
                    'photo_url' => $photoUrl,
                    'status' => $photoCheck->status()
                ]);
                return false;
            }
    
            $response = Http::attach(
                'photo', 
                file_get_contents($photoUrl), 
                'photo.jpg'
            )->post("{$this->apiUrl}/sendPhoto", [
                'chat_id' => $chatId
            ]);
    
            if (!$response->successful()) {
                Log::error('Failed to send photo message', [
                    'chat_id' => $chatId,
                    'error' => $response->json(),
                    'status' => $response->status()
                ]);
                return false;
            }
    
            Log::info('Photo sent successfully', [
                'chat_id' => $chatId,
                'response' => $response->json()
            ]);
    
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending photo message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chatId' => $chatId
            ]);
            return false;
        }
    }

    public function sendDocument(string $chatId, string $fileUrl, string $filename = null): bool
    {
        try {
            Log::info('Attempting to send document', [
                'chat_id' => $chatId,
                'file_url' => $fileUrl
            ]);

            // Download the file
            $response = Http::get($fileUrl);
            $tempFile = tempnam(sys_get_temp_dir(), 'telegram_doc_');
            file_put_contents($tempFile, $response->body());

            // Send document to Telegram
            $response = Http::attach(
                'document',
                fopen($tempFile, 'r'),
                $filename ?? 'document'
            )->post("{$this->apiUrl}/sendDocument", [
                'chat_id' => $chatId,
                'caption' => $filename
            ]);

            // Clean up temp file
            unlink($tempFile);

            if (!$response->successful()) {
                Log::error('Failed to send document', [
                    'chat_id' => $chatId,
                    'error' => $response->json(),
                    'status' => $response->status()
                ]);
                return false;
            }

            Log::info('Document sent successfully', [
                'chat_id' => $chatId,
                'response' => $response->json()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram document send error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'file_url' => $fileUrl
            ]);
            return false;
        }
    }
}
