<?php

namespace Modules\Chat\Policies;

use Modules\Chat\Models\Chat;
use Modules\User\Models\User;

class ChatPolicy
{
    public function view(User $user, Chat $chat): bool
    {
        return $user->id === $chat->sender_id || $user->id === $chat->receiver_id;
    }

    public function update(User $user, Chat $chat): bool
    {
        return $user->id === $chat->sender_id || $user->id === $chat->receiver_id;
    }

    public function delete(User $user, Chat $chat): bool
    {
        return $user->id === $chat->sender_id || $user->id === $chat->receiver_id;
    }

    public function markAsRead(User $user, Chat $chat): bool
    {
        return $user->id === $chat->sender_id || $user->id === $chat->receiver_id;
    }
}
