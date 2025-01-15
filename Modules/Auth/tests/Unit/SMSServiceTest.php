<?php

namespace Modules\Auth\Tests\Unit;

use Exception;
use Illuminate\Support\Facades\Log;
use Ipe\Sdk\Facades\SmsIr;
use Modules\Auth\Services\SMSService;
use Tests\TestCase;

class SMSServiceTest extends TestCase
{
    private SMSService $smsService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->smsService = new SMSService();
    }

    public function test_it_sends_a_regular_message_successfully(): void
    {
        $phone = '09123456789';
        $message = 'This is a test message.';

        SmsIr::shouldReceive('likeToLikeSend')
            ->once()
            ->with(
                config('auth.sms.smsir.line_number'),
                [$message],
                [$phone],
                null
            )
            ->andReturn(['status' => 1]);

        $this->smsService->send($phone, $message);
    }

    public function test_it_logs_an_error_when_sending_a_regular_message_fails(): void
    {
        $phone = '09123456789';
        config(['sms.smsir.line_number' => '3000xxx']);

        SmsIr::shouldReceive('likeToLikeSend')
            ->once()
            ->andThrow(new Exception('Failed to send SMS'));

        Log::shouldReceive('error')
            ->once()
            ->with("Failed to send SMS to {$phone}: Failed to send SMS");

        $this->smsService->send($phone, 'This is a test message.');
    }

    public function test_it_sends_an_otp_message_successfully(): void
    {
        $phone = '09123456789';
        $code = 123456;

        SmsIr::shouldReceive('verifySend')
            ->once()
            ->with(
                $phone,
                config('auth.sms.smsir.template_id'),
                [
                    [
                        'name' => 'code',
                        'value' => (string) $code,
                    ],
                ]
            )
            ->andReturn(['status' => 1]);

        $this->smsService->sendOtp($phone, $code);
    }

    public function test_it_logs_the_error_when_sending_otp_message_fails(): void
    {
        $phone = '09123456789';

        SmsIr::shouldReceive('verifySend')
            ->once()
            ->andThrow(new Exception('Failed to send OTP'));

        Log::shouldReceive('error')
            ->once()
            ->with("Failed to send OTP to {$phone}: Failed to send OTP");

        $this->smsService->sendOtp($phone, 123456);
    }

    public function test_it_uses_the_correct_configuration_values(): void
    {
        SmsIr::shouldReceive('likeToLikeSend')
            ->once()
            ->with(config('auth.sms.smsir.line_number'), $this->anything(), $this->anything(), $this->anything())
            ->andReturn(['status' => 1]);

        SmsIr::shouldReceive('verifySend')
            ->once()
            ->with($this->anything(), config('auth.sms.smsir.template_id'), $this->anything())
            ->andReturn(['status' => 1]);

        $this->smsService->send('09123456789', 'Test message');
        $this->smsService->sendOtp('09123456789', 123456);
    }
}
