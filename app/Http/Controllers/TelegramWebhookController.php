<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TelegramUser;
use App\Models\TelegramMessage;
use App\Events\UserAdded;
use App\Events\TelegramMessageReceived;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        try {
            Log::info('Telegram webhook received', $request->all());

            $data = $request->all();
            if (!isset($data['message'])) {
                return response()->json(['status' => 'success']); // Ignore non-message updates
            }

            $chatId = $data['message']['chat']['id'];
            $firstName = $data['message']['chat']['first_name'] ?? '';
            $lastName = $data['message']['chat']['last_name'] ?? '';
            $username = $data['message']['chat']['username'] ?? '';
            $messageText = $data['message']['text'] ?? '';

            // Create or update TelegramUser
            $telegramUser = TelegramUser::updateOrCreate(
                ['chat_id' => $chatId],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'username' => $username,
                ]
            );

            // Store the message
            $message = TelegramMessage::create([
                'telegram_user_id' => $telegramUser->id,
                'content' => $messageText,
                'from_admin' => false,
                'is_read' => false,
            ]);

            // Broadcast both events
            broadcast(new UserAdded($telegramUser))->toOthers();
            broadcast(new TelegramMessageReceived($telegramUser->id, $messageText))->toOthers();

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}