<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderShipmentStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
    * Get the channels the event should broadcast on.
    *
    * @return Channel
    */
    public function broadcastOn(): Channel
    {
        return new Channel('my.channel');
    }

    /**
    * The event's broadcast name.
    */
    public function broadcastAs(): string
    {
        return 'my.event';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'status' => "SUCCESSFUL",
            'referenceNumber' => 12345
        ];
    }
}
