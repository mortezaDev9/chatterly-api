<?php

declare(strict_types = 1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Auth\Services\AuthService;
use Modules\User\Models\Device;
use Modules\User\Models\User;
use Modules\User\Transformers\UserResource;

readonly class AuthenticateUserController
{
    public function __construct(private AuthService $authService)
    {
    }

    public function login(Request $request): JsonResponse
    {
        if ($request->has('phone')) {
            $validated = $request->validate([
                'phone' => phone_validation_rules(false),
            ]);

            $this->authService->requestVerificationCode($validated['phone']);

            return json(['message' => __('Verification code sent successfully to your phone.')]);
        }

        if ($request->has('code')) {
            $validated = $request->validate([
                'code'    => ['required', 'numeric', 'digits:6'],
                'browser' => ['required', 'string', 'max:32'],
            ]);

            if ((int) $validated['code'] === cache('code')) {
                $user = User::wherePhone(cache('phone'))->first();

                if ($user) {
                    cache()->forget('code');
                    cache()->forget('phone');

                    if (Device::whereUserId($user->id)->exists()) {
                        $this->authService->sendSecurityAlert(
                            $user->phone,
                            __("We noticed a new login from {$validated['browser']} on {$request->ip()}. If this wasn’t you, please terminate all other sessions immediately to secure your account.")
                        );
                    }

                    $deviceId = $this->authService->generateDeviceId();

                    Device::create([
                        'user_id'       => $user->id,
                        'device_id'     => $deviceId,
                        'browser'       => $validated['browser'],
                        'ip_address'    => $request->ip(),
                    ]);

                    return json([
                        'data' => [
                            'user'  => UserResource::make($user),
                            'token' => $user->createToken($deviceId)->plainTextToken,
                        ],
                    ]);
                }

                return json(['message' => __('No user found with this phone number.')], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return json(['message' => __('Incorrect login code.')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return json(['message' => __('Invalid request.')], Response::HTTP_BAD_REQUEST);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        $request->user()->devices()->whereDeviceId($token->name)->delete();

        $token->delete();

        return json(status: Response::HTTP_OK);
    }

    public function logoutAllOtherSessions(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        $request->user()->tokens()->where('id', '!=', $token->id)->delete();

        $request->user()->devices()->where('device_id', '!=', $token->name)->delete();

        return json(status: Response::HTTP_OK);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        $request->user()->devices()->delete();

        return json(status: Response::HTTP_OK);
    }
}
