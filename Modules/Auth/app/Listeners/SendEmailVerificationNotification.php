<?php

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Registered;
use Modules\Auth\Notifications\VerifyEmail;
use Modules\User\Models\User;

class SendEmailVerificationNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user instanceof User ? $event->user : User::whereId($event->user->id)->first();

        $user->notify(new VerifyEmail($user));
    }
}
