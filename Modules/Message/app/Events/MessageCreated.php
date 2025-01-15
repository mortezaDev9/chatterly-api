<?php

namespace Modules\Message\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Models\Chat;
use Modules\Message\Models\Message;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public readonly Message $message)
    {
        //
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): PrivateChannel|Channel
    {
        return $this->message->messageable_type === Chat::class ?
            new PrivateChannel('chat.' . $this->message->messageable_id) :
            new Channel('group.public.' . $this->message->messageable_id);
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }
}
