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
}
