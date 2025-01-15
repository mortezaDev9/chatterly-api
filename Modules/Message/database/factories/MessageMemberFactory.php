<?php

namespace Modules\Message\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Message\Models\Message;
use Modules\User\Models\User;
use Modules\Message\Models\MessageMember;

class MessageMemberFactory extends Factory
{
    protected $model = MessageMember::class;

    public function definition(): array
    {
        $messageId = Message::inRandomOrder()->value('id') ?? Message::factory()->create()->id;

        $existingMemberIds = MessageMember::whereMessageId($messageId)->pluck('member_id')->toArray();

        $memberId = User::whereNotIn('id', $existingMemberIds)
            ->inRandomOrder()
            ->value('id') ?? User::factory()->create()->id;

        return [
            'message_id' => $messageId,
            'member_id'  => $memberId,
        ];
    }
}

