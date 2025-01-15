<?php

namespace Modules\ContactUser\Policies;

use Modules\ContactUser\Models\ContactUser;
use Modules\User\Models\User;

class ContactUserPolicy
{
    public function view(User $user, ContactUser $contact): bool
    {
        return $user->id === $contact->user_id;
    }

    public function update(User $user, ContactUser $contact): bool
    {
        return $user->id === $contact->user_id;
    }

    public function delete(User $user, ContactUser $contact): bool
    {
        return $user->id === $contact->user_id;
    }
}
