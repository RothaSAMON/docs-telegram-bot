<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// class TelegramMessageReceived
// {
//     use Dispatchable, InteractsWithSockets, SerializesModels;

//     /**
//      * Create a new event instance.
//      */
//     public function __construct()
//     {
//         //
//     }

//     /**
//      * Get the channels the event should broadcast on.
//      *
//      * @return array<int, \Illuminate\Broadcasting\Channel>
//      */
//     public function broadcastOn(): array
//     {
//         return [
//             new PrivateChannel('channel-name'),
//         ];
//     }
// }
class TelegramMessageReceived implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $telegram_user_id,
        public string $message
    ) {}

    public function broadcastOn()
    {
        Log::info('Message received broadcaston');

        return new Channel('telegram-messages');
    }

    public function broadcastAs()
    {
        Log::info('Message received broadcastAs');
        return 'MessageReceived';
    }
}