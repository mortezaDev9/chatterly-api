<?php

namespace Modules\Contact\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contact\Models\Contact;
use Modules\User\Models\User;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return ['user_id' => User::inRandomOrder()->value('id') ?? User::factory()->create()->id];
    }
}

