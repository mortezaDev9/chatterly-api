<?php

namespace Modules\Auth\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Modules\User\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $phone = '12345678911';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'phone'      => '12345678910',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);
    }

    public function test_user_can_request_verification_code_with_unregistered_phone(): void
    {
        $response = $this->postJson('api/v1/register', ['phone' => $this->phone]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['message' => __('Verification code sent successfully to your phone.')]);

        $this->assertNotNull(cache('phone'));
        $this->assertEquals($this->phone, cache('phone'));
        $this->assertNotNull(cache('code'));
    }

    public function test_user_cannot_request_verification_code_with_registered_phone(): void
    {
        $response = $this->postJson('api/v1/register', ['phone' => $this->user->phone]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['phone']);
        $response->assertJsonFragment([
            'errors' => [
                'phone' => [__('validation.unique', ['attribute' => 'phone'])]
            ]
        ]);

        $this->assertNull(cache('phone'));
        $this->assertNull(cache('code'));
    }

    public function test_user_cannot_request_verification_code_with_invalid_phone(): void
    {
        $testCases = [
            'too_short' => ['phone' => '123'],
            'too_long'  => ['phone' => '1234567890123456'],
        ];

        foreach ($testCases as $testCase => $payload) {
            $response = $this->postJson('api/v1/register', $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['phone']);

            $this->assertNull(cache('phone'));
            $this->assertNull(cache('code'));
        }
    }

    public function test_user_can_verify_code(): void
    {
        cache(['phone' => $this->phone]);
        cache(['code' => 123456]);

        $response = $this->postJson('api/v1/register', ['code' => cache('code')]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertTrue(cache('isVerified'));
    }

    public function test_user_cannot_verify_phone_with_incorrect_code(): void
    {
        cache(['phone' => $this->phone]);
        cache(['code' => 123456]);

        $response = $this->postJson('api/v1/register', ['code' => 654321]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['code']);
        $response->assertJson(['message' => __('Invalid code.')]);

        $this->assertNull(cache('isVerified'));
    }

    public function test_user_cannot_verify_code_with_invalid_code_format(): void
    {
        $testCases = [
            'non_numeric' => ['code' => 'abcdef'],
            'too_short'   => ['code' => '12345'],
            'too_long'    => ['code' => '1234567']
        ];

        foreach ($testCases as $testCase => $payload) {
            $response = $this->postJson('api/v1/register', $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['code']);
        }
    }

    public function test_user_cannot_register_without_verifying_phone(): void
    {
        $response = $this->postJson('api/v1/register', ['first_name' => 'Morteza', 'last_name' => 'Ayashi']);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson(['message' => __('Please verify your phone first.')]);
    }

    public function test_user_can_complete_registration(): void
    {
        $this->postJson('api/v1/register', ['phone' => $this->phone]);
        $this->postJson('api/v1/register', ['code' => cache('code')]);

        $response = $this->postJson('api/v1/register', [
            'first_name' => 'Morteza',
            'last_name' => 'Ayashi',
            'browser' => 'Chrome',
        ]);

        $user = User::wherePhone($response->json('data.user.phone'))->first();

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertExactJson([
            'data' => [
                'user' => [
                    'id'             => $user->id,
                    'user_id'        => $user->user_id,
                    'full_name'      => $user->full_name,
                    'bio'            => $user->bio,
                    'phone'          => $user->phone,
                    'avatar'         => $user->avatar,
                    'remember_token' => $user->remember_token,
                    'created_at'     => $user->created_at,
                    'updated_at'     => $user->updated_at,
                ],
                'token' => $response->json('data.token'),
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'phone'      => $this->phone,
            'first_name' => 'Morteza',
            'last_name'  => 'Ayashi',
        ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id'   => $user->id,
            'tokenable_type' => User::class,
        ]);

        $this->assertDatabaseHas('devices', [
            'user_id'   => $user->id,
            'device_id' => $user->devices()->first()->device_id,
            'browser'   => 'Chrome',
        ]);
    }

    public function test_user_cannot_register_with_invalid_first_name(): void
    {
        cache(['phone' => $this->phone]);
        cache(['code' => 123456]);
        cache(['isVerified' => true]);

        $testCases = [
            'empty' => ['first_name' => ''],
            'too_long' => ['first_name' => str_repeat('a', 256)]
        ];

        foreach ($testCases as $case => $payload) {
            $response = $this->postJson('api/v1/register', array_merge($payload, [
                'last_name' => 'Ayashi',
            ]));

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['first_name']);
        }
    }

    public function test_user_can_register_without_last_name(): void
    {
        cache(['phone' => $this->phone]);
        cache(['code' => 123456]);
        cache(['isVerified' => true]);

        $response = $this->postJson('api/v1/register', [
            'first_name' => 'Morteza',
            'last_name' => null,
            'browser' => 'Chrome',
        ]);

        $user = $response->json('data.user');

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'user' => [
                    'id'             => $user['id'],
                    'user_id'        => $user['user_id'],
                    'full_name'      => $user['full_name'],
                    'bio'            => $user['bio'],
                    'phone'          => $user['phone'],
                    'avatar'         => $user['avatar'],
                    'remember_token' => $user['remember_token'],
                    'created_at'     => $user['created_at'],
                    'updated_at'     => $user['updated_at'],
                ],
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'phone' => $this->phone,
            'first_name' => 'Morteza',
            'last_name' => null,
        ]);
    }

    public function test_user_cannot_register_with_invalid_last_name(): void
    {
        cache(['phone' => $this->phone]);
        cache(['code' => 123456]);
        cache(['isVerified' => true]);

        $response = $this->postJson('api/v1/register', [
            'first_name' => 'Morteza',
            'last_name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['last_name']);
    }

    public function test_user_cannot_register_without_browser(): void
    {
        cache(['phone' => $this->phone]);
        cache(['code' => 123456]);
        cache(['isVerified' => true]);

        $response = $this->postJson('api/v1/register', [
            'first_name' => 'Morteza',
            'last_name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['browser']);
    }

    public function test_user_cannot_register_with_invalid_browser(): void
    {
        cache(['phone' => $this->phone]);
        cache(['code' => 123456]);
        cache(['isVerified' => true]);

        $response = $this->postJson('api/v1/register', [
            'first_name' => 'Morteza',
            'browser' => str_repeat('a', 33),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['browser']);
    }

    public function test_invalid_request_returns_bad_request_response(): void
    {
        $response = $this->postJson('api/v1/register', [
            'invalid_field' => 'value',
        ]);

        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertJson(['message' => __('Invalid request.')]);
    }
}
