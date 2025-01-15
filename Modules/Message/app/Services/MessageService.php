<?php

declare(strict_types=1);

namespace Modules\Message\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;

class MessageService
{
    public function markGroupMessagesAsRead(Group $group): void
    {
        $messageIds = $group->messages()
            ->where('sender_id', '!=', Auth::id())
            ->whereDoesntHave('readers', function ($query) {
                $query->whereMemberId(Auth::id());
            })
            ->pluck('id');

        Auth::user()->readMessages()->attach($messageIds);
    }

    public function markChatMessagesAsRead(Chat $chat): void
    {
        $messageIds = $chat->messages()
            ->where('sender_id', $chat->getOtherUser()->id)
            ->pluck('id');

        Message::whereIn('id', $messageIds)
            ->where('status', '!=', MessageStatus::READ->value)
            ->update(['status' => MessageStatus::READ->value]);
    }
}
