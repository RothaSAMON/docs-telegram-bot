<?php

namespace App\Http\Controllers;

use App\Services\TelegramMessageProcessor;
use Illuminate\Http\Request;
use App\Models\TelegramUser;
use App\Models\TelegramMessage;
use App\Events\UserAdded;
use App\Events\TelegramMessageReceived;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected TelegramMessageProcessor $messageProcessor;

    public function __construct(TelegramMessageProcessor $messageProcessor)
    {
        $this->messageProcessor = $messageProcessor;
    }

    public function __invoke(Request $request)
    {
        try {
            // Log the raw request data
            Log::info('Raw webhook data received', [
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);
    
            $data = $request->all();
    
            // Validate required data
            if (!isset($data['message']) || !isset($data['message']['chat'])) {
                Log::warning('Invalid webhook data structure', ['data' => $data]);
                return response()->json(['status' => 'error', 'message' => 'Invalid data structure'], 400);
            }
    
            $chatId = $data['message']['chat']['id'];
            $firstName = $data['message']['chat']['first_name'] ?? '';
            $lastName = $data['message']['chat']['last_name'] ?? '';
            $username = $data['message']['chat']['username'] ?? '';
    
            // Log user data
            Log::info('Processing user data', [
                'chatId' => $chatId,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'username' => $username
            ]);
    
            try {
                // Create or update TelegramUser
                $telegramUser = TelegramUser::updateOrCreate(
                    ['chat_id' => $chatId],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'username' => $username,
                    ]
                );
            } catch (\Exception $e) {
                Log::error('Database error creating/updating user', [
                    'error' => $e->getMessage(),
                    'chatId' => $chatId
                ]);
                throw $e;
            }
    
            // Process message with error handling
            try {
                $message = $this->messageProcessor->processMessage($data['message'], $telegramUser->id);
                
                if ($message) {
                    Log::info('Message processed', [
                        'messageId' => $message->id,
                        'type' => isset($data['message']['photo']) ? 'photo' : 'text',
                        'userId' => $telegramUser->id
                    ]);
    
                    // Broadcast with error handling
                    try {
                        broadcast(new UserAdded($telegramUser))->toOthers();
                        
                        // Prepare message content for broadcast
                        $broadcastContent = $message->file_type === 'photo' 
                            ? '[Photo]' . ($message->content ? ': ' . $message->content : '')
                            : $message->content;
    
                        broadcast(new TelegramMessageReceived($telegramUser->id, $broadcastContent))->toOthers();
                        
                        Log::info('Message broadcast content', [
                            'content' => $broadcastContent,
                            'type' => $message->file_type ?? 'text'
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Broadcasting error', [
                            'error' => $e->getMessage(),
                            'messageId' => $message->id
                        ]);
                        // Continue execution even if broadcasting fails
                    }
                } else {
                    Log::error('Message processing failed but did not throw exception');
                }
            } catch (\Exception $e) {
                Log::error('Message processing error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'messageData' => $data['message']
                ]);
                throw $e;
            }
    
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);
            
            // Return 200 status to Telegram even on error to prevent retries
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 200);
        }
    }
}