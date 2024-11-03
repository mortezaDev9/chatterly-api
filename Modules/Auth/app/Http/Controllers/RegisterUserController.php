<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Http\Requests\RegisterUserRequest;
use Modules\Shared\Services\ShareService;
use Modules\User\Models\User;

class RegisterUserController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth::register');
    }

    public function register(RegisterUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'slug'     => Str::slug($validated['username']),
            'email'    => $validated['email'],
            'password' => $validated['password'],
        ]);

        Auth::login($user);

        event(new Registered($user));

        ShareService::successToast('You have registered successfully');

        return to_route('verification.notice');
    }
}
