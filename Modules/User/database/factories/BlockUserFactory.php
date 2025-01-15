<?php

namespace Modules\User\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Models\BlockUser;
use Modules\User\Models\User;

class BlockUserFactory extends Factory
{
    protected $model = BlockUser::class;

    public function definition(): array
    {
        $userId = User::inRandomOrder()->value('id') ?? User::factory()->create()->id;

        $existingBlockedUserIds = BlockUser::whereUserId($userId)->pluck('blocked_user_id')->toArray();

        $blockedUser = User::whereNotIn('id', array_merge([$userId], $existingBlockedUserIds))
            ->inRandomOrder()
            ->first() ?? User::factory()->create();

        return [
            'user_id'         => $userId,
            'blocked_user_id' => $blockedUser->id,
            'blocked_at'      => $this->faker->dateTimeBetween('-1 year'),
        ];
    }
}
