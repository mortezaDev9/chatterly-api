<?php

namespace Modules\Auth\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Emails\WelcomeEmail;
use Modules\User\Models\User;

class SendWelcomeEmail
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $user = $event->user instanceof User ? $event->user : User::whereId($event->user->id)->first();

        Mail::to($user)->send(new WelcomeEmail($user));
    }
}
