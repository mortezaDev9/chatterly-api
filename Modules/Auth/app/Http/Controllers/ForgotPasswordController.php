<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Services\AuthService;
use Modules\User\Models\User;

class ForgotPasswordController extends Controller
{
    public function showForgotPasswordForm(): View
    {
        return view('auth::forgot-password');
    }

    public function sendResetPasswordEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email']]);
        $email     = $validated['email'];
        $user      = User::whereEmail($email)->first();

        if ($user) {
            $token = Str::random(60);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'      => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $resetLink = url(route('reset-password.form', [
                'token' => $token,
            ]));

            $user->notify(new ResetPassword($resetLink));
        }

        return back()->with(['info' => 'A password reset link has been sent to your email address.']);
    }
}
