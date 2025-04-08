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
}
