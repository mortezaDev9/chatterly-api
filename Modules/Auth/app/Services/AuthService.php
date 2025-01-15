<?php

declare(strict_types = 1);

namespace Modules\Auth\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\User\Models\Device;
use Modules\User\Models\User;

class AuthService
{
    public function __construct(private readonly SMSService $smsService)
    {
    }

    public function generateDeviceId(): string
    {
        do {
            $deviceId = Str::uuid()->toString();
        } while (Device::whereDeviceId($deviceId)->exists());

        return $deviceId;
    }

    public function requestVerificationCode(string $phone): void
    {
        $code = rand(100000, 999999);

        $this->smsService->sendOtp($phone, $code);

        cache(['code'  => $code], now()->addMinutes(5));
        cache(['phone' => $phone], now()->addMinutes(5));
    }

    public function sendSecurityAlert(string $phone, string $message): void
    {
        $this->smsService->send($phone, $message);
    }
}
