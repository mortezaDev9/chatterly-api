<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Auth\Services\AuthService;
use Modules\User\Models\User;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'phone'      => '12345678910',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);
    }

    public function test_user_can_request_verification_code_for_registered_phone(): void
    {
        $response = $this->postJson('api/v1/login', ['phone' => $this->user->phone]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['message' => __('Verification code sent successfully to your phone.')]);

        $this->assertNotNull(cache('phone'));
        $this->assertEquals($this->user->phone, cache('phone'));
        $this->assertNotNull(cache('code'));
    }

    public function test_user_cannot_request_verification_code_for_unregistered_phone(): void
    {
        $response = $this->postJson('api/v1/login', ['phone' => '12345678911']);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson(['message' => __('validation.exists', ['attribute' => 'phone'])]);

        $this->assertNull(cache('phone'));
        $this->assertNull(cache('code'));
    }

    public function test_user_cannot_request_verification_code_with_invalid_phone_format(): void
    {
        $testCases = [
            'too_short' => ['phone' => '123'],
            'too_long'  => ['phone' => '1234567890123456'],
        ];

        foreach ($testCases as $testCase => $payload) {
            $response = $this->postJson('api/v1/login', $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['phone']);
        }
    }

    public function test_user_can_login_with_correct_code(): void
    {
        $this->postJson('api/v1/login', ['phone' => $this->user->phone]);

        $authServiceMock = $this->mock(AuthService::class);
        $authServiceMock->shouldNotReceive('sendSms');

        $response = $this->postJson('api/v1/login', ['code' => cache('code'), 'browser' => 'Chrome']);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertExactJson([
            'data' => [
                'user' => [
                    'id'             => $this->user->id,
                    'user_id'        => $this->user->user_id,
                    'full_name'      => $this->user->full_name,
                    'bio'            => $this->user->bio,
                    'phone'          => $this->user->phone,
                    'avatar'         => $this->user->avatar,
                    'remember_token' => $this->user->remember_token,
                    'created_at'     => $this->user->created_at,
                    'updated_at'     => $this->user->updated_at,
                ],
                'token' => $response->json('data.token'),
            ],
        ]);

        $this->assertNull(cache('phone'));
        $this->assertNull(cache('code'));

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $this->user->id,
            'tokenable_type' => User::class,
        ]);

        $this->assertDatabaseHas('devices', [
            'user_id'   => $this->user->id,
            'device_id' => $this->user->devices()->first()->device_id,
            'browser'   => 'Chrome',
        ]);
    }

    public function test_user_can_login_from_multiple_devices(): void
    {
        cache(['phone' => $this->user->phone]);
        cache(['code' => 123456]);

        $firstLoginResponse = $this->postJson('api/v1/login', ['code' => 123456, 'browser' => 'Chrome']);

        $firstLoginResponse->assertStatus(Response::HTTP_OK);

        $firstDeviceId = $this->user->devices()->first()->device_id;

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
            'name' => $firstDeviceId,
        ]);

        $this->assertDatabaseHas('devices', [
            'user_id'   => $this->user->id,
            'device_id' => $firstDeviceId,
            'browser'   => 'Chrome',
        ]);

        cache(['phone' => $this->user->phone]);
        cache(['code' => 123456]);

        $secondLoginResponse = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.2'])
            ->postJson('api/v1/login', ['code' => 123456, 'browser' => 'Opera']);

        $secondLoginResponse->assertStatus(Response::HTTP_OK);

        $secondDeviceId = $this->user->devices()->latest('id')->first()->device_id;

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
            'name' => $secondDeviceId,
        ]);

        $this->assertDatabaseHas('devices', [
            'user_id'   => $this->user->id,
            'device_id' => $secondDeviceId,
            'browser'   => 'Opera',
        ]);

        $this->assertCount(2, $this->user->devices);
    }

    public function test_user_cannot_login_with_incorrect_code(): void
    {
        cache(['phone' => $this->user->phone]);
        cache(['code' => 123456]);

        $response = $this->postJson('api/v1/login', ['code' => 654321, 'browser' => 'Chrome']);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson(['message' => __('Incorrect login code.')]);
    }

    public function test_user_cannot_login_without_browser(): void
    {
        cache(['phone' => $this->user->phone]);
        cache(['code' => 123456]);

        $response = $this->postJson('api/v1/login', ['code' => 123456]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['browser']);
    }

    public function test_user_cannot_login_with_unregistered_phone(): void
    {
        $response = $this->postJson('api/v1/login', ['phone' => '12345678911']);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson(['message' => __('validation.exists', ['attribute' => 'phone'])]);
    }

    public function test_user_get_notified_when_there_is_login_from_a_new_device(): void
    {
        $this->user->devices()->create([
            'user_id'    => $this->user->id,
            'device_id'  => Str::random(32),
            'browser'    => 'Chrome',
            'ip_address' => '127.0.0.1',
        ]);

        $authServiceMock = $this->partialMock(AuthService::class);
        $authServiceMock->shouldReceive('sendSecurityAlert')
            ->once()
            ->with(
                $this->user->phone,
                __("We noticed a new login from Chrome on 127.0.0.1. If this wasn’t you, please terminate all other sessions immediately to secure your account.")
            )
            ->andReturn(true);

        cache(['phone' => $this->user->phone]);
        cache(['code' => 123456]);

        $response = $this->postJson('api/v1/login', ['code' => 123456, 'browser' => 'Chrome']);

        $response->assertStatus(Response::HTTP_OK);
    }
    public function test_user_can_logout(): void
    {
        cache(['phone' => $this->user->phone]);
        cache(['code' => 123456]);

        $loginResponse = $this->postJson('api/v1/login', ['code' => 123456, 'browser' => 'Chrome']);

        $token = $loginResponse->json('data.token');
        $deviceId = $this->user->devices()->first()->device_id;

        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('api/v1/logout');

        $logoutResponse->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'tokenable_type' => User::class,
        ]);

        $this->assertDatabaseMissing('devices', [
            'user_id' => $this->user->id,
            'device_id' => $deviceId,
        ]);
    }

    public function test_user_can_logout_all_other_sessions(): void
    {
        cache(['phone' => $this->user->phone]);
        cache(['code' => 123456]);

        $loginResponse = $this->postJson('/api/v1/login', ['code' => 123456, 'browser' => 'Chrome']);

        $this->user->createToken('device-2')->plainTextToken;
        $this->user->createToken('device-3')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 3);

        $this->user->devices()->create([
            'device_id' => 'device-2',
            'browser' => 'Chrome',
            'ip_address' => '127.0.0.1',
        ]);
        $this->user->devices()->create([
            'device_id' => 'device-3',
            'browser' => 'Chrome',
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertDatabaseCount('devices', 3);

        $token = $loginResponse->json('data.token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/logout-all-other-sessions');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'device-2',
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => 'device-3',
        ]);

        $this->assertDatabaseMissing('devices', [
            'user_id' => $this->user->id,
            'device_id' => 'device-2',
        ]);
        $this->assertDatabaseMissing('devices', [
            'user_id' => $this->user->id,
            'device_id' => 'device-3',
        ]);

        $deviceId = $this->user->devices()->first()->device_id;

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $this->user->id,
            'name' => $deviceId,
        ]);
        $this->assertDatabaseHas('devices', [
            'user_id' => $this->user->id,
            'device_id' => $deviceId,
        ]);
    }

    public function test_user_can_logout_from_all_devices(): void
    {
        Sanctum::actingAs($this->user);

        $this->user->createToken('device-1')->plainTextToken;
        $this->user->createToken('device-2')->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->user->devices()->create([
            'device_id' => 'device-1',
            'browser' => 'Chrome',
            'ip_address' => '127.0.0.1',
        ]);
        $this->user->devices()->create([
            'device_id' => 'device-2',
            'browser' => 'Chrome',
            'ip_address' => '127.0.0.1',
        ]);

        $this->assertDatabaseCount('devices', 2);

        $response = $this->postJson('api/v1/logout-all');

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseEmpty('personal_access_tokens');
        $this->assertDatabaseEmpty('devices');
    }
}
