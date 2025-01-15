<?php

namespace Modules\Device\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\Device;
use Modules\User\Models\User;
use Tests\TestCase;

class DeviceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);
    }

    public function test_user_can_view_all_their_devices(): void
    {
        Device::factory(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/devices');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonCount(5, 'data');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'device_id',
                    'browser',
                    'ip_address',
                    'logged_at',
                ],
            ],
        ]);
    }

    public function test_user_cannot_view_other_users_devices(): void
    {
        $otherUser = User::factory()->create();

        Device::factory(5)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/v1/devices');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonCount(0, 'data');
    }

    public function test_user_can_view_empty_device_list(): void
    {
        $response = $this->getJson('/api/v1/devices');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonCount(0, 'data');
    }
}
