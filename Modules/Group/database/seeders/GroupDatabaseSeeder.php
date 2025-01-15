<?php

namespace Modules\Group\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Group\Models\Group;
use Modules\Group\Models\GroupMember;

class GroupDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Group::factory(10)->create();
        GroupMember::factory(100)->create();
    }
}
