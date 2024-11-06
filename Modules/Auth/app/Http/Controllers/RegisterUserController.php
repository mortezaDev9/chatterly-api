<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Http\Requests\RegisterUserRequest;
use Modules\User\Models\User;

class RegisterUserController extends Controller
{
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'phone'      => $validated['phone'],
            'first_name' => $validated['first_name'],
        ]);

        Auth::login($user);

        return response()->json($user->toArray());
    }
}
