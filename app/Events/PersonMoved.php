<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PersonMoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public float $lat;
    public float $lng;

    /**
     * Create a new event instance.
     */
    public function __construct($lat,$lng)
    {
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return new Channel('trackerApp');
        // return [
        //     new PrivateChannel('channel-name'),
        // ];
    }
}
