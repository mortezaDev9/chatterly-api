<?php

namespace Modules\Device\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\Device;

class DeviceDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Device::factory(30)->create();
    }
}
