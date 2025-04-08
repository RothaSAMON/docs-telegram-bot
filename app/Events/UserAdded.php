<?php

namespace App\Events;

use App\Models\TelegramUser;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// class UserAdded
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
class UserAdded implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(public TelegramUser $user)
    {}

    public function broadcastOn()
    {
        Log::info('UserAdded broadcaston');
        return new Channel('users');
    }

    public function broadcastAs()
    {
        Log::info('UserAdded broadcastAs');
        return 'user.added';
    }
}
