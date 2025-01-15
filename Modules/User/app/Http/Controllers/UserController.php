<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\User\Events\UserBlocked;
use Modules\User\Events\UserUnblocked;
use Modules\User\Models\User;
use Modules\User\Transformers\BlockUserResource;

class UserController
{
    public function blockedUsers(): JsonResponse
    {
        return json([
            'data' => BlockUserResource::collection(
                Auth::user()->blockedUsers()->get(),
            ),
        ]);
    }

    public function blockUser(Request $request): JsonResponse
    {
        $validated = $request->validate(['user_id' => ['required', 'numeric', 'exists:users,id']]);

        if ($validated['user_id'] === Auth::id()) {
            return json([
                'message' => __('You cannot block yourself.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (Auth::user()->blockedUsers()->whereBlockedUserId($validated['user_id'])->exists()) {
            return json([
                'message' => __('User is already blocked.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Auth::user()->blockedUsers()->attach($validated['user_id']);

        broadcast(new UserBlocked(User::whereId($validated['user_id'])->first()));

        return json();
    }

    public function unblockUser(Request $request): JsonResponse
    {
        $validated = $request->validate(['user_id' => ['required', 'numeric', 'exists:users,id']]);

        if ($validated['user_id'] === Auth::id()) {
            return json([
                'message' => __('You cannot unblock yourself.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (! Auth::user()->blockedUsers()->whereBlockedUserId($validated['user_id'])->exists()) {
            return json([
                'message' => __('User is not blocked.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Auth::user()->blockedUsers()->detach($validated['user_id']);

        broadcast(new UserUnblocked(User::whereId($validated['user_id'])->first()));

        return json();
    }
}
