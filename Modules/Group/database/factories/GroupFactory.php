<?php

namespace Modules\Group\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Group\Models\Group;
use Modules\User\Models\User;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'group_id'    => Str::uuid()->toString(),
            'owner_id'    => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'name'        => fake()->words(2, true),
            'picture'     => fake()->imageUrl(),
            'description' => fake()->sentence,
        ];
    }
}

