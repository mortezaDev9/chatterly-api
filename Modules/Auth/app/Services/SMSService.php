<?php

declare(strict_types = 1);

namespace Modules\Auth\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Ipe\Sdk\Facades\SmsIr;

class SMSService
{
    public function send(string $phone, string $message): void
    {
        try {
            SmsIr::likeToLikeSend(
                config('auth.sms.smsir.line_number'),
                [$message],
                [$phone],
                null,
            );
        } catch (Exception $e) {
            Log::error("Failed to send SMS to {$phone}: " . $e->getMessage());
        }
    }

    public function sendOtp(string $phone, int $code): void
    {
        try {
            SmsIr::verifySend(
                $phone,
                config('auth.sms.smsir.template_id'),
                [
                    [
                        'name' => 'code',
                        'value' => (string) $code
                    ]
                ]
            );
        } catch (Exception $e) {
            Log::error("Failed to send OTP to {$phone}: " . $e->getMessage());
        }
    }
}
