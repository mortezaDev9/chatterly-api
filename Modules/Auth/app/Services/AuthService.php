<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Http\Requests\LoginUserRequest;
use Modules\Auth\Http\Requests\RegisterUserRequest;
use Modules\User\Http\Requests\UpdatePasswordRequest;
use Modules\User\Models\User;

readonly class AuthService
{
    public function updatePassword(UpdatePasswordRequest $request): void
    {
        $validated = $request->validated();
        $user      = Auth::user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        if (Hash::check($validated['new_password'], $user->password)) {
            throw ValidationException::withMessages([
                'new_password' => 'The new password cannot be the same as the current password.',
            ]);
        }

        $user->forceFill(['password' => Hash::make($validated['new_password'])]);
        $user->save();
    }
}
