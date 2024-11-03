<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Chat\Database\Seeders\ChatDatabaseSeeder;
use Modules\Contact\Database\Seeders\ContactDatabaseSeeder;
use Modules\Group\Database\Seeders\GroupDatabaseSeeder;
use Modules\Message\Database\Seeders\MessageDatabaseSeeder;
use Modules\Notification\Database\Seeders\NotificationDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ChatDatabaseSeeder::class);
        $this->call(ContactDatabaseSeeder::class);
        $this->call(GroupDatabaseSeeder::class);
        $this->call(MessageDatabaseSeeder::class);
        $this->call(NotificationDatabaseSeeder::class);
        $this->call(UserDatabaseSeeder::class);
    }
}
