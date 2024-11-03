<?php

namespace Modules\Chat\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Chat;
use Modules\Message\Models\Message;
use Modules\User\Models\User;

class ChatFactory extends Factory
{
    protected $model = Chat::class;

    public function definition(): array
    {
        return ['user_id' => User::inRandomOrder()->value('id') ?? User::factory()->create()->id];
    }
}
