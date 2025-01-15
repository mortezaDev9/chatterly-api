<?php

namespace Modules\Group\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Modules\Group\Events\UserAddedToGroup;
use Modules\Group\Events\UserRemovedFromGroup;
use Modules\Group\Events\UserPromotedToAdmin;
use Modules\Group\Models\Group;
use Modules\Group\Models\GroupMember;
use Modules\Group\Transformers\GroupMemberResource;

class GroupMemberController
{
    public function index(Group $group): JsonResponse
    {
        return json([
            'data' => GroupMemberResource::collection(
                $group->members()->get()
            ),
        ]);
    }

    public function addMember(Group $group, Request $request): JsonResponse
    {
        Gate::authorize('addMember', $group);

        $validated = $request->validate(['user_id' => ['required', 'numeric']]);

        if ($group->members()->whereMemberId($validated['user_id'])->exists()) {
            return json([
                'message' => __('User is already a member of this group.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $group->members()->attach($validated['user_id']);

        broadcast(new UserAddedToGroup($group))->toOthers();

        return json(status: Response::HTTP_CREATED);
    }

    public function removeMember(Group $group, GroupMember $member): JsonResponse
    {
        Gate::authorize('removeMember', $group);

        if ($member->member_id === $group->owner_id) {
            return json([
                'message' => __('You cannot remove the owner of the group.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($member->is_admin) {
            return json([
                'message' => __('You cannot remove the admin of the group.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $group->members()->detach($member->member_id);

        broadcast(new UserRemovedFromGroup($group))->toOthers();

        return json(status: Response::HTTP_NO_CONTENT);
    }

    public function promoteToAdmin(Group $group, GroupMember $member): JsonResponse
    {
        Gate::authorize('promoteToAdmin', $group);

        if (! $group->members()->whereMemberId($member->member_id)->exists()) {
            return json([
                'message' => __('User is not a member of the group.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($member->is_admin) {
            return json([
                'message' => __('User is already an admin in the group.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $group->members()->updateExistingPivot($member->member_id, ['is_admin' => true]);

        broadcast(new UserPromotedToAdmin($group));

        return json(status: Response::HTTP_OK);
    }
}
