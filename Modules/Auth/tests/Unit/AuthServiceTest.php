<?php

namespace Modules\Auth\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Modules\Auth\Services\AuthService;
use Modules\Auth\Services\SMSService;
use Modules\User\Models\Device;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;
    private MockInterface $smsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsService = $this->mock(SMSService::class);
        $this->authService = new AuthService($this->smsService);
    }

    public function test_it_does_not_generate_duplicate_device_id(): void
    {
        $existingDeviceId = Str::uuid()->toString();
        Device::factory()->create(['device_id' => $existingDeviceId]);

        $deviceId = $this->authService->generateDeviceId();

        $this->assertNotEquals($existingDeviceId, $deviceId);
        $this->assertTrue(Str::isUuid($deviceId));
    }

    public function test_it_requests_a_verification_code(): void
    {
        $phone = '09123456789';

        $this->smsService->shouldReceive('sendOtp')
            ->once()
            ->with($phone, $this->logicalAnd(
                $this->greaterThanOrEqual(100000),
                $this->lessThanOrEqual(999999),
            ))
            ->andReturn(['status' => 1]);

        $this->authService->requestVerificationCode($phone);

        $this->assertNotNull(cache('code'));
        $this->assertEquals($phone, cache('phone'));
    }

    public function test_it_sends_a_security_alert(): void
    {
        $phone = '09123456789';
        $message = 'This is a security alert.';

        $this->smsService->shouldReceive('send')
            ->once()
            ->with($phone, $message)
            ->andReturn(['status' => 1]);

        $this->authService->sendSecurityAlert($phone, $message);
    }
}
