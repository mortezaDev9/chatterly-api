<?php

namespace Modules\Chat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Chat\Models\Chat;
use Modules\User\Models\User;

class ChatFactory extends Factory
{
    protected $model = Chat::class;

    public function definition(): array
    {
        $senderId = User::inRandomOrder()->value('id') ?? User::factory()->create()->id;

        $receiverId = User::where('id', '!=', $senderId)
            ->inRandomOrder()
            ->value('id') ?? User::factory()->create()->id;

        if (Chat::where(function ($query) use ($senderId, $receiverId) {
            $query->whereSenderId($senderId)->whereReceiverId($receiverId);
        })->orWhere(function ($query) use ($senderId, $receiverId) {
            $query->whereSenderId($receiverId)->whereReceiverId($senderId);
        })->exists()) {
            return $this->definition();
        }

        return [
            'sender_id'   => $senderId,
            'receiver_id' => $receiverId,
        ];
    }
}
