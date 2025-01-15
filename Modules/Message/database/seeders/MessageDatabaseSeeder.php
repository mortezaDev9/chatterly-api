<?php

namespace Modules\Message\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Message\Models\Message;
use Modules\Message\Models\MessageMember;
use Modules\Message\Models\MessageStatus;

class MessageDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Message::factory(100)->create();
        MessageMember::factory(200)->create();
    }
}
