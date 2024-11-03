<?php

namespace Modules\Message\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Message\Models\Message;
use Modules\Message\Models\MessageStatus;
use Modules\User\Models\User;

class MessageStatusFactory extends Factory
{
    protected $model = MessageStatus::class;

    public function definition(): array
    {
        $isRead = fake()->randomElement([true, false]);

        return [
            'message_id' => Message::inRandomOrder()->value('id') ?? Message::factory()->create()->id,
            'user_id'    => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'is_read'    => $isRead,
            'read_at'    => $isRead ? now() : null,
        ];
    }
}

