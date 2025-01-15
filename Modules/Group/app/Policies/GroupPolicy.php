<?php

namespace Modules\Group\Policies;

use Modules\Group\Models\Group;
use Modules\User\Models\User;

class GroupPolicy
{
    public function update(User $user, Group $group): bool
    {
        return $user->id === $group->owner_id;
    }

    public function delete(User $user, Group $group): bool
    {
        return $user->id === $group->owner_id;
    }

    public function transferOwnership(User $user, Group $group): bool
    {
        return $user->id === $group->owner_id;
    }

    public function markAsRead(User $user, Group $group): bool
    {
        return $group->members()
            ->whereMemberId($user->id)
            ->exists();
    }
}
