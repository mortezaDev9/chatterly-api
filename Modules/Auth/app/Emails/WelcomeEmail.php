<?php

namespace Modules\Auth\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\User\Models\User;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public readonly User $user)
    {
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('auth::mail.welcome', ['user' => $this->user])
            ->subject('Welcome to '.config('app.name'));
    }
}
