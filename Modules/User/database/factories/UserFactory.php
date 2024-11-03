<?php

namespace Modules\User\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\User\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'user_id'        => Str::uuid()->toString(),
            'first_name'     => fake()->firstName,
            'last_name'      => fake()->randomElement([$this->faker->lastName, null]),
            'phone'          => fake()->unique()->phoneNumber,
            'avatar'         => fake()->imageUrl(
                100, 100, 'people', true, 'Avatar'
            ),
            'remember_token' => Str::random(64),
        ];
    }
}
