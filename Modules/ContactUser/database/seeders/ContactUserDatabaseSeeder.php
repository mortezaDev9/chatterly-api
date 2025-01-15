<?php

namespace Modules\ContactUser\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ContactUser\Models\ContactUser;

class ContactUserDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContactUser::factory(10)->create();
    }
}
