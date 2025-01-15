<?php

namespace Modules\ContactUser\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\ContactUser\Events\ContactCreated;
use Modules\ContactUser\Events\ContactDeleted;
use Modules\ContactUser\Events\ContactUpdated;
use Modules\ContactUser\Http\Requests\StoreContactRequest;
use Modules\ContactUser\Http\Requests\UpdateContactRequest;
use Modules\ContactUser\Models\ContactUser;
use Modules\ContactUser\Transformers\ContactUserResource;
use Modules\User\Models\User;
use Modules\User\Transformers\UserResource;

class ContactUserController
{
    public function index(): JsonResponse
    {
         return json([
             'data' => ContactUserResource::collection(
                 Auth::user()->contacts()->get()
             ),
         ]);
    }

    public function show(ContactUser $contact): JsonResponse
    {
        Gate::authorize('view', $contact);

        return json(['data' => UserResource::make($contact->contactedUser)]);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::wherePhone($validated['phone'])->first();

        if (! $user) {
            return json(['message' => __('User not found.')], Response::HTTP_NOT_FOUND);
        }

        if ($user->id === Auth::id()) {
            return json(['message' => __('You cannot add yourself as a contact.')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (ContactUser::whereUserId(Auth::id())
            ->whereContactedUserId($user->id)
            ->exists()
        ) {
            return json(['message' => __('Contact already exists.')], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Auth::user()->contacts()->attach($user->id, [
            'first_name' => $validated['first_name'] ?? $user->first_name,
            'last_name'  => $validated['last_name'] ?? $user->last_name,
        ]);

        $contactedUser = Auth::user()->contacts()->whereContactedUserId($user->id)->first();

        broadcast(new ContactCreated($contactedUser->pivot));

        return json(['data' => ContactUserResource::make($contactedUser)], Response::HTTP_CREATED);
    }

    public function update(UpdateContactRequest $request, ContactUser $contact): JsonResponse
    {
        Gate::authorize('update', $contact);

        $validated = $request->validated();

        $changes = array_filter($validated, function ($value, $key) use ($contact) {
            return $contact->$key !== $value;
        }, ARRAY_FILTER_USE_BOTH);

        if (! empty($changes)) {
            Auth::user()->contacts()->updateExistingPivot($contact->contacted_user_id, $changes);

            broadcast(new ContactUpdated($contact));
        }

        return json(['data' => ContactUserResource::make($contact->contactedUser)]);
    }

    public function destroy(ContactUser $contact): JsonResponse
    {
        Gate::authorize('delete', $contact);

        $contact->delete();

        broadcast(new ContactDeleted($contact));

        return json(status: Response::HTTP_NO_CONTENT);
    }
}
