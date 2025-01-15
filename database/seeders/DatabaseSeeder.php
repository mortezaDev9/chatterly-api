<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Chat\Database\Seeders\ChatDatabaseSeeder;
use Modules\ContactUser\Database\Seeders\ContactUserDatabaseSeeder;
use Modules\Device\Database\Seeders\DeviceDatabaseSeeder;
use Modules\Group\Database\Seeders\GroupDatabaseSeeder;
use Modules\Message\Database\Seeders\MessageDatabaseSeeder;
use Modules\User\Database\Seeders\BlockUserDatabaseSeeder;
use Modules\User\Database\Seeders\UserDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserDatabaseSeeder::class);
        $this->call(DeviceDatabaseSeeder::class);
        $this->call(BlockUserDatabaseSeeder::class);
        $this->call(ContactUserDatabaseSeeder::class);
        $this->call(ChatDatabaseSeeder::class);
        $this->call(GroupDatabaseSeeder::class);
        $this->call(MessageDatabaseSeeder::class);
    }
}
