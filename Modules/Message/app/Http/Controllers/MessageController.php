<?php

declare(strict_types=1);

namespace Modules\Message\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\Message\Events\MessageDeleted;
use Modules\Message\Events\MessageUpdated;
use Modules\Message\Events\MessageCreated;
use Modules\Message\Http\Requests\StoreMessageRequest;
use Modules\Message\Http\Requests\UpdateMessageRequest;
use Modules\Message\Models\Message;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Notifications\NewMessage;
use Modules\Message\Transformers\MessageResource;

class MessageController
{
    public function store(StoreMessageRequest $request): JsonResponse
    {
        $messageableClass  = $request->input('messageable_type') === 'chat' ? Chat::class : Group::class;
        $messageable       = $messageableClass::findOrFail($request->input('messageable_id'));

        Gate::authorize('create', $messageable);

        if ($messageableClass === Chat::class) {
            $otherUser = $messageable->getOtherUser();

            if (Auth::user()->blockedUsers()->whereBlockedUserId($otherUser->id)->exists()) {
                return json([
                    'message' => __('You have blocked this user.'),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($otherUser->blockedUsers()->whereBlockedUserId(Auth::id())->exists()) {
                return json([
                    'message' => __('This user has blocked you.'),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $validated = $request->validated();

        $message = Message::create([
            'messageable_id'   => $messageable->id,
            'messageable_type' => $messageableClass,
            'sender_id'        => Auth::id(),
            'content'          => $validated['content'],
            'status'           => MessageStatus::SENT->value,
        ]);

        broadcast(new MessageCreated($message))->toOthers();

        if ($messageableClass === Chat::class) {
            $messageable->getOtherUser()->notify(new NewMessage($message));
        } else {
            foreach ($messageable->members as $member) {
                if ($member->id !== Auth::id()) {
                    $member->notify(new NewMessage($message));
                }
            }
        }

        return json([
            'data' => [
                'message' => MessageResource::make($message)
            ],
        ], Response::HTTP_CREATED);
    }

    public function update(UpdateMessageRequest $request, Message $message): JsonResponse
    {
        Gate::authorize('update', $message);

        if (! in_array($message->status, [MessageStatus::SENT->value, MessageStatus::READ->value])) {
            return json([
                'message' => __('This message is not editable.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validated();

        $message->update([
            'content'   => $validated['content'],
            'is_edited' => true,
        ]);

        broadcast(new MessageUpdated($message))->toOthers();

        return json(['data' => MessageResource::make($message)]);
    }

    public function destroy(Message $message): JsonResponse
    {
        Gate::authorize('delete', $message);

        $message->delete();

        broadcast(new MessageDeleted($message))->toOthers();

        return json(status: Response::HTTP_NO_CONTENT);
    }
}
