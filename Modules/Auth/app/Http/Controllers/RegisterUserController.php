<?php

declare(strict_types = 1);

namespace Modules\Auth\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Services\AuthService;
use Modules\User\Models\Device;
use Modules\User\Models\User;
use Modules\User\Transformers\UserResource;

readonly class RegisterUserController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        if ($request->has('phone')) {
            $validated = $request->validate([
                'phone' => phone_validation_rules(),
            ]);

            $this->authService->requestVerificationCode($validated['phone']);

            return json(['message' => __('Verification code sent successfully to your phone.')]);
        }

        if ($request->has('code')) {
            $validated = $request->validate(['code' => ['required', 'numeric', 'digits:6']]);

            if ((int) $validated['code'] !== cache('code')) {
                return json([
                    'message' => __('Invalid code.'),
                    'errors' => ['code' => [__('validation.invalid', ['attribute' => 'code'])]],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            cache(['isVerified' => true], now()->addMinutes(5));

            return json();
        }

        if ($request->has('first_name')) {
            if (! cache('isVerified') || is_null(cache('isVerified'))) {
                return json(['message' => __('Please verify your phone first.')], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name'  => ['nullable', 'string', 'max:255'],
                'browser'    => ['required', 'string', 'max:32'],
            ]);

            try {
                $result = DB::transaction(function () use ($validated, $request) {
                    $user = User::create([
                        'first_name' => $validated['first_name'],
                        'last_name'  => $validated['last_name'] ?? null,
                        'phone'      => cache('phone'),
                    ]);

                    $deviceId = $this->authService->generateDeviceId();

                    Device::create([
                        'user_id'    => $user->id,
                        'device_id'  => $deviceId,
                        'browser'    => $validated['browser'],
                        'ip_address' => $request->ip(),
                    ]);

                    return ['user' => $user, 'deviceId' => $deviceId];
                });

                cache()->forget('code');
                cache()->forget('phone');
                cache()->forget('isVerified');

                $user = $result['user'];

                return json([
                    'data' => [
                        'user'  => UserResource::make($user),
                        'token' => $user->createToken($result['deviceId'])->plainTextToken,
                    ],
                ], Response::HTTP_CREATED);
            } catch (Exception $e) {
                Log::error('Transaction for creating user failed: ' . $e->getMessage());

                return json([
                    'message' => __('An error occurred while processing your request. Please try again.'),
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return json(['message' => __('Invalid request.')], Response::HTTP_BAD_REQUEST);
    }
}
