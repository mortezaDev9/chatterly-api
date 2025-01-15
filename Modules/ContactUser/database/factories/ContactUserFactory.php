<?php

namespace Modules\ContactUser\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ContactUser\Models\ContactUser;
use Modules\User\Models\User;

class ContactUserFactory extends Factory
{
    protected $model = ContactUser::class;

    public function definition(): array
    {
        $userId = User::inRandomOrder()->value('id') ?? User::factory()->create()->id;

        $existingContactIds = ContactUser::whereUserId($userId)->pluck('contacted_user_id')->toArray();

        $contactedUser = User::whereNotIn('id', array_merge([$userId], $existingContactIds))
            ->inRandomOrder()
            ->first() ?? User::factory()->create();

        return [
            'user_id'           => $userId,
            'contacted_user_id' => $contactedUser->id,
            'first_name'        => $contactedUser->first_name,
            'last_name'         => $contactedUser->last_name,
        ];
    }
}
