<?php

namespace Modules\Group\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Group\Models\Group;
use Modules\Group\Models\GroupMember;
use Modules\User\Models\User;

class GroupMemberFactory extends Factory
{
    protected $model = GroupMember::class;

    public function definition(): array
    {
        return [
            'group_id'  => Group::inRandomOrder()->value('id') ?? Group::factory()->create()->id,
            'user_id'   => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'is_admin'  => fake()->boolean(20),
            'joined_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}

