<?php


namespace Modules\Device\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Models\Device;
use Modules\User\Models\User;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        $userId = User::inRandomOrder()->value('id') ?? User::factory()->create()->id;

        $existingDeviceUserIds = Device::whereUserId($userId)->pluck('user_id')->toArray();

        $deviceUser = User::whereNotIn('id', array_merge([$userId], $existingDeviceUserIds))
            ->inRandomOrder()
            ->first() ?? User::factory()->create();

        return [
            'user_id'    => $deviceUser->id,
            'device_id'  => fake()->uuid,
            'browser'    => fake()->randomElement(['Chrome', 'Firefox', 'Opera', 'Safari', 'Edge']),
            'ip_address' => fake()->ipv4,
            'logged_at'  => fake()->dateTimeBetween('-1 year'),
        ];
    }
}
