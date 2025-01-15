<?php

namespace Modules\Message\Policies;

use Modules\Chat\Models\Chat;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;
use Modules\User\Models\User;

class MessagePolicy
{
    public function update(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id;
    }

    public function delete(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id;
    }
}
