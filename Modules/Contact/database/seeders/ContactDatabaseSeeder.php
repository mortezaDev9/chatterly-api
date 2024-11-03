<?php

namespace Modules\Contact\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Contact\Models\Contact;

class ContactDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Contact::factory(10)->create();
    }
}
