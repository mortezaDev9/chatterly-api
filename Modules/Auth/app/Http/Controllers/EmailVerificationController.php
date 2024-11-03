<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Auth\Notifications\VerifyEmail;
use Modules\Shared\Services\ShareService;
use Modules\User\Models\User;

class EmailVerificationController extends Controller
{
    public function showVerificationNotice(): View
    {
        return view('auth::verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        if (is_null($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()]);
            $user->save();

            event(new Verified($user));

            ShareService::successToast('Your email has been verified');

            return to_route('home');
        }

        return to_route('home');
    }

    public function resendVerificationEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->notify(new VerifyEmail($user));

        return back()->with(['info' => 'Verification link sent!']);
    }
}
