<?php

namespace Modules\Message\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Chat\Models\Chat;
use Modules\Message\Models\Message;

class NewMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly Message $message)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['broadcast'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => 'You have a new message',
            'data' => [
                'id'      => $this->message->id,
                'content' => $this->message->content,
                'sender'  => $this->message->sender->full_name,
                'sent_at' => $this->message->sent_at,
            ],
        ]);
    }

    public function broadcastType(): string
    {
        return 'new.message';
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->message->messageable_type === Chat::class ? 'chat.' : 'group.private.' . $this->message->messageable_id);
    }
}
