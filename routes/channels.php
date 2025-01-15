<?php

declare(strict_types=1);

use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\User\Models\User;

Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return $user->id === $userId;
});

Broadcast::channel('chat.{chat}', function (User $user, Chat $chat) {
    return $user->id === $chat->sender_id || $user->id === $chat->receiver_id;
});

Broadcast::channel('group.private.{group}', function (User $user, Group $group) {
    return $group->members()->whereMemberId($user->id)->exists();
});

Broadcast::channel('group.presence.{group}', function (User $user, Group $group) {
    if (Auth::check()) {
        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
        ];
    }

    return null;
});
