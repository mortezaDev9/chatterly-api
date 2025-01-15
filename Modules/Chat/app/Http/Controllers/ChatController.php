<?php

namespace Modules\Chat\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Modules\Chat\Events\ChatCreated;
use Modules\Chat\Events\ChatDeleted;
use Modules\Chat\Events\ChatMessagesRead;
use Modules\Chat\Events\UserEnteredChat;
use Modules\Chat\Http\Requests\StoreChatRequest;
use Modules\Chat\Models\Chat;
use Modules\Chat\Transformers\ChatDetailedResource;
use Modules\Chat\Transformers\ChatSimpleResource;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;
use Modules\Message\Models\MessageStatus as MessageStatusModel;
use Modules\Message\Services\MessageService;
use Modules\User\Models\User;

readonly class ChatController
{
    public function __construct(private MessageService $messageService)
    {
    }

    public function index(): JsonResponse
    {
        return json([
            'data' => ChatSimpleResource::collection(Chat::with(['sender', 'receiver', 'latestMessage'])
                ->whereSenderId(Auth::id())
                ->orWhere('receiver_id', Auth::id())
                ->get()
            ),
        ]);
    }

    public function show(Chat $chat): JsonResponse
    {
        Gate::authorize('view', $chat);

        $chat->load([
            'messages',
            'sender',
            'receiver',
        ]);

        $chat->messages = $chat->messages->filter(function ($message) use ($chat) {
            return (
                ($chat->sender_id === Auth::id() || $chat->receiver_id === Auth::id()) &&
                ($chat->sender_id === $chat->getOtherUser()->id || $chat->receiver_id === $chat->getOtherUser()->id) &&
                ! in_array($message->status, [MessageStatus::FAILED->value, MessageStatus::PENDING->value])
            );
        });

        $this->messageService->markChatMessagesAsRead($chat);

        broadcast(new UserEnteredChat($chat))->toOthers();

        return json(['data' => ChatDetailedResource::make($chat)]);
    }

    public function store(StoreChatRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $otherUser = Auth::id() === $validated['sender_id'] ?
            User::whereId($validated['receiver_id'])->first() :
            User::whereId($validated['sender_id'])->first();

        if (Auth::user()->blockedUsers()->whereBlockedUserId($otherUser->id)->exists()
        ) {
            return json([
                'message' => __('You have blocked this user.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($otherUser->blockedUsers()->whereBlockedUserId(Auth::id())->exists()
        ) {
            return json([
                'message' => __('This user has blocked you.')
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (Chat::whereSenderId($validated['sender_id'])
            ->whereReceiverId($validated['receiver_id'])
            ->exists()) {
            return json([
                'data' => ChatSimpleResource::make(Chat::whereSenderId($validated['sender_id'])
                    ->whereReceiverId($validated['receiver_id'])
                    ->first())
            ]);
        }

        $chat = Chat::create([
            'sender_id'   => $validated['sender_id'],
            'receiver_id' => $validated['receiver_id'],
        ]);

        broadcast(new ChatCreated($chat));

        return json(['data' => ChatSimpleResource::make($chat)], Response::HTTP_CREATED);
    }

    public function destroy(Chat $chat): JsonResponse
    {
        Gate::authorize('delete', $chat);

        $chat->delete();

        broadcast(new ChatDeleted($chat));

        return json(status: Response::HTTP_NO_CONTENT);
    }

    public function markAsRead(Chat $chat): JsonResponse
    {
        Gate::authorize('markAsRead', $chat);

        $this->messageService->markChatMessagesAsRead($chat);

        broadcast(new ChatMessagesRead($chat));

        return json();
    }
}
