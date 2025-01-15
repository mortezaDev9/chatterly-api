<?php

namespace Modules\Message\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;
use Modules\User\Models\User;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $messageable = fake()->randomElement([
            ['id' => Chat::inRandomOrder()->value('id') ?? Chat::factory()->create()->id, 'type' => Chat::class],
            ['id' => Group::inRandomOrder()->value('id') ?? Group::factory()->create()->id, 'type' => Group::class],
        ]);

        return [
            'messageable_id'   => $messageable['id'],
            'messageable_type' => $messageable['type'],
            'sender_id'        => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'content'          => fake()->text,
            'status'           => fake()->randomElement(MessageStatus::cases()),
        ];
    }
}
