<?php

namespace Modules\Group\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\Group\Events\GroupCreated;
use Modules\Group\Events\GroupDeleted;
use Modules\Group\Events\GroupMessagesRead;
use Modules\Group\Events\GroupUpdated;
use Modules\Group\Events\OwnershipTransferred;
use Modules\Group\Events\UserJoinedGroup;
use Modules\Group\Events\UserLeftGroup;
use Modules\Group\Http\Requests\StoreGroupRequest;
use Modules\Group\Http\Requests\UpdateGroupRequest;
use Modules\Group\Models\Group;
use Modules\Group\Transformers\GroupDetailedResource;
use Modules\Group\Transformers\GroupSimpleResource;
use Modules\Message\Services\MessageService;
use Modules\User\Models\User;

readonly class GroupController
{
    public function __construct(private MessageService $messageService)
    {
    }

    public function index(): JsonResponse
    {
        return json([
            'data' => GroupSimpleResource::collection(
                Group::with(['latestMessage.sender'])
                    ->whereHas('members', function ($query) {
                        $query->whereMemberId(Auth::id());
                    })
                    ->get()
            ),
        ]);
    }

    public function show(Group $group): JsonResponse
    {
        $group->load(['members', 'messages']);

        $this->messageService->markGroupMessagesAsRead($group);

        return json(['data' => GroupDetailedResource::make($group)]);
    }

    public function store(StoreGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $group = DB::transaction(function () use ($validated) {
                $group = Group::create([
                    'group_id'    => $validated['group_id'],
                    'owner_id'    => Auth::id(),
                    'name'        => $validated['name'],
                    'picture'     => $validated['picture'] ?? null,
                    'description' => $validated['description'] ?? null,
                ]);

                $group->members()->attach(Auth::id(), ['is_admin' => true]);

                return $group;
            });

            $group->load(['members', 'messages']);

            broadcast(new GroupCreated($group));

            return json(['data' => GroupDetailedResource::make($group)], Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Transaction for creating group failed: ' . $e->getMessage());

            return json([
                'message' => __('An error occurred while processing your request. Please try again.'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(UpdateGroupRequest $request, Group $group): JsonResponse
    {
        Gate::authorize('update', $group);

        $validated = $request->validated();

        $group->update([
            'group_id'    => $validated['group_id'] ?? $group->group_id,
            'name'        => $validated['name'] ?? $group->name,
            'picture'     => $validated['picture'] ?? $group->picture,
            'description' => $validated['description'] ?? $group->description,
        ]);

        $group->load(['members', 'messages']);

        broadcast(new GroupUpdated($group));

        return json(['data' => GroupDetailedResource::make($group)]);
    }

    public function destroy(Group $group): JsonResponse
    {
        Gate::authorize('delete', $group);

        $group->delete();

        broadcast(new GroupDeleted($group));

        return json(status: Response::HTTP_NO_CONTENT);
    }

    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate(['group_id' => ['required', 'numeric', 'exists:groups,id']]);

        $group = Group::whereId($validated['group_id'])->first();

        if ($group->members()->whereMemberId(Auth::id())->exists()) {
            return json([
                'message' => __('You are already a member of this group.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $group->members()->attach(Auth::id());

        $group->load(['members', 'messages']);

        $this->messageService->markGroupMessagesAsRead($group);

        broadcast(new UserJoinedGroup($group));

        return json(['data' => GroupDetailedResource::make($group)], Response::HTTP_CREATED);
    }

    public function leave(Group $group): JsonResponse
    {
        if ($group->owner_id === Auth::id()) {
            return json([
                'message' => __('The owner cannot leave the group. Transfer ownership to leave or delete the group instead.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! $group->members()->whereMemberId(Auth::id())->exists()) {
            return json([
                'message' => __('You are not a member of this group.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $group->members()->detach(Auth::id());

        broadcast(new UserLeftGroup($group));

        return json(status: Response::HTTP_NO_CONTENT);
    }

    public function transferOwnership(Group $group, User $user): JsonResponse
    {
        Gate::authorize('transferOwnership', $group);

        if (! $group->members()->whereMemberId($user->id)->exists()) {
            return json([
                'message' => __('You cannot transfer ownership to non existent member.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($group->owner_id === $user->id) {
            return json([
                'message' => __('You cannot transfer ownership to yourself.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $group->update(['owner_id' => $user->id]);

        broadcast(new OwnershipTransferred($group));

        return json(status: Response::HTTP_NO_CONTENT);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $validated = $request->validate(['group_id' => ['required', 'numeric', 'exists:groups,id']]);

        $group = Group::whereId($validated['group_id'])->first();

        Gate::authorize('markAsRead', $group);

        $this->messageService->markGroupMessagesAsRead($group);

        broadcast(new GroupMessagesRead($group));

        return json();
    }
}
