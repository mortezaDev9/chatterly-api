<?php

namespace Modules\Notification\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Notification\Enums\NotificationType;
use Modules\Notification\Models\Notification;
use Modules\User\Models\User;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'type'    => fake()->randomElement(NotificationType::getValues()),
            'content' => fake()->text,
            'is_read' => fake()->boolean(),
        ];
    }
}

