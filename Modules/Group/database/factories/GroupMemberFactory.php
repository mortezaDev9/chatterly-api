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
        $group = Group::inRandomOrder()->first() ?? Group::factory()->create();

        $existingMemberIds = GroupMember::whereGroupId($group->id)->pluck('member_id');

        $memberId = User::whereNotIn('id', $existingMemberIds)
            ->inRandomOrder()
            ->value('id') ?? User::factory()->create()->id;

        return [
            'group_id'  => $group->id,
            'member_id' => $memberId,
            'is_admin'  => $group->owner_id === $memberId || fake()->boolean(20),
            'joined_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
