<?php

namespace Modules\Chat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Events\ChatCreated;
use Modules\Chat\Events\ChatDeleted;
use Modules\Chat\Events\ChatMessagesRead;
use Modules\Chat\Events\UserEnteredChat;
use Modules\Chat\Models\Chat;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;
use Modules\User\Models\User;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);

        Event::fake();
    }

    public function test_user_can_view_all_chats(): void
    {
        Chat::factory(10)->create(['sender_id' => $this->user->id]);

        $response = $this->getJson('/api/v1/chats');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(10, 'data');
        $response->assertExactJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'sender_id',
                    'receiver_id',
                    'user' => [
                        'id',
                        'full_name',
                        'avatar',
                    ],
                    'latestMessage',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }

    public function test_user_cannot_view_others_chats(): void
    {
        $users = User::factory(2)->create();

        Chat::factory(10)->create([
            'sender_id'   => $users->get(0)->id,
            'receiver_id' => $users->get(1)->id,
        ]);

        $response = $this->getJson('/api/v1/chats');

        $response->assertJson(['data' => []]);
    }

    public function test_user_can_view_chat(): void
    {
        $secondUser = User::factory()->create();
        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $response = $this->getJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([
            'data' => [
                'id'          => $chat->id,
                'sender_id'   => $this->user->id,
                'receiver_id' => $secondUser->id,
                'user'        => [
                    'id'             => $secondUser->id,
                    'user_id'        => $secondUser->user_id,
                    'full_name'      => $secondUser->full_name,
                    'bio'            => $secondUser->bio,
                    'phone'          => $secondUser->phone,
                    'avatar'         => $secondUser->avatar,
                    'remember_token' => $secondUser->remember_token,
                    'created_at'     => $secondUser->created_at->toISOString(),
                    'updated_at'     => $secondUser->updated_at->toISOString(),
                ],
                'messages'    => [],
                'created_at'  => $chat->created_at->toISOString(),
                'updated_at'  => $chat->updated_at->toISOString(),
            ],
        ]);

        Event::assertDispatched(UserEnteredChat::class, function ($event) use ($chat) {
            return $event->chat->id === $chat->id;
        });
    }

    public function test_unread_messages_marked_as_read_when_user_view_chat(): void
    {
        $secondUser = User::factory()->create();

        $chat = Chat::factory()->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $unreadMessages = Message::factory(3)->create([
            'messageable_id' => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id' => $secondUser->id,
        ]);

        $response = $this->getJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(Response::HTTP_OK);

        foreach ($unreadMessages as $message) {
            $this->assertDatabaseHas('messages', [
                'id'     => $message->id,
                'status' => MessageStatus::READ->value,
            ]);
        }
    }

    public function test_user_cannot_view_others_chat(): void
    {
        $users = User::factory(2)->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $users->get(0)->id,
            'receiver_id' => $users->get(1)->id,
        ]);

        $response = $this->getJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(UserEnteredChat::class);
    }


    public function test_user_can_view_chat_with_data(): void
    {
        $secondUser = User::factory()->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $message = Message::factory()->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
            'status'           => MessageStatus::SENT->value,
        ]);
        $secondMessage = Message::factory()->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $secondUser->id,
            'status'           => MessageStatus::SENT->value,
        ]);
        $thirdMessage = Message::factory()->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
            'status'           => MessageStatus::SENT->value,
        ]);

        $response = $this->getJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([
            'data' => [
                'id'          => $chat->id,
                'sender_id'   => $this->user->id,
                'receiver_id' => $secondUser->id,
                'user'        => [
                    'id'             => $secondUser->id,
                    'user_id'        => $secondUser->user_id,
                    'full_name'      => $secondUser->full_name,
                    'bio'            => $secondUser->bio,
                    'phone'          => $secondUser->phone,
                    'avatar'         => $secondUser->avatar,
                    'remember_token' => $secondUser->remember_token,
                    'created_at'     => $secondUser->created_at->toISOString(),
                    'updated_at'     => $secondUser->updated_at->toISOString(),
                ],
                'messages'    => [
                    [
                        'id'               => $message->id,
                        'messageable_id'   => $message->messageable_id,
                        'messageable_type' => $message->messageable_type,
                        'sender_id'        => $message->sender_id,
                        'content'          => $message->content,
                        'status'           => $message->status,
                        'is_edited'        => $message->is_edited,
                        'sent_at'          => $message->sent_at->toISOString(),
                    ],
                    [
                        'id'               => $secondMessage->id,
                        'messageable_id'   => $secondMessage->messageable_id,
                        'messageable_type' => $secondMessage->messageable_type,
                        'sender_id'        => $secondMessage->sender_id,
                        'content'          => $secondMessage->content,
                        'status'           => $secondMessage->status,
                        'is_edited'        => $secondMessage->is_edited,
                        'sent_at'          => $secondMessage->sent_at->toISOString(),
                    ],
                    [
                        'id'               => $thirdMessage->id,
                        'messageable_id'   => $thirdMessage->messageable_id,
                        'messageable_type' => $thirdMessage->messageable_type,
                        'sender_id'        => $thirdMessage->sender_id,
                        'content'          => $thirdMessage->content,
                        'status'           => $thirdMessage->status,
                        'is_edited'        => $thirdMessage->is_edited,
                        'sent_at'          => $thirdMessage->sent_at->toISOString(),
                    ],
                ],
                'created_at'  => $chat->created_at->toISOString(),
                'updated_at'  => $chat->updated_at->toISOString(),
            ],
        ]);

        Event::assertDispatched(UserEnteredChat::class, function ($event) use ($chat) {
            return $event->chat->id === $chat->id;
        });
    }

    public function test_user_does_not_receive_failed_or_pending_messages(): void
    {
        $secondUser = User::factory()->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $failedMessage = Message::factory()->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $secondUser->id,
            'status'           => MessageStatus::FAILED->value,
        ]);

        $pendingMessage = Message::factory()->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $secondUser->id,
            'status'           => MessageStatus::PENDING->value,
        ]);

        $response = $this->getJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonMissing(['messages.id' => $failedMessage->id]);
        $response->assertJsonMissing(['messages.id' => $pendingMessage->id]);

        Event::assertDispatched(UserEnteredChat::class, function ($event) use ($chat) {
            return $event->chat->id === $chat->id;
        });
    }

    public function test_user_can_create_chat(): void
    {
        $secondUser = User::factory()->create();

        $response = $this->postJson('/api/v1/chats', [
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $chat = $response->json('data');

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('chats', [
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        Event::assertDispatched(ChatCreated::class, function ($event) use ($chat) {
            return $event->chat->id === $chat['id'];
        });
    }

    public function test_user_cannot_create_chat_with_invalid_data(): void
    {
        $testCases = [
            'missing_sender'        => [
                'receiver_id' => 2,
            ],
            'missing_receiver'      => [
                'sender_id' => 3,
            ],
            'non_numeric_sender'    => [
                'sender_id'   => 'abc',
                'receiver_id' => 2,
            ],
            'non_numeric_receiver'  => [
                'sender_id'   => 2,
                'receiver_id' => 'xyz',
            ],
            'non_existing_sender'   => [
                'sender_id'   => 2,
                'receiver_id' => 3,
            ],
            'non_existing_receiver' => [
                'sender_id'   => 2,
                'receiver_id' => 3,
            ],
            'same_sender_receiver'  => [
                'sender_id'   => 2,
                'receiver_id' => 2,
            ],
            'missing_both'          => [],
            'null_sender'           => [
                'sender_id'   => null,
                'receiver_id' => 2,
            ],
            'null_receiver'         => [
                'sender_id'   => 2,
                'receiver_id' => null,
            ],
        ];

        foreach ($testCases as $testCase => $payload) {
            $response = $this->postJson('api/v1/chats', $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            $this->assertDatabaseEmpty('chats');

            Event::assertNotDispatched(ChatCreated::class);
        }
    }

    public function test_user_cannot_create_chat_with_blocked_user(): void
    {
        $user = User::factory()->create();

        $this->user->blockedUsers()->attach($user->id);

        $response = $this->postJson('api/v1/chats', [
            'sender_id'   => $this->user->id,
            'receiver_id' => $user->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseEmpty('chats');

        Event::assertNotDispatched(ChatCreated::class);
    }

    public function test_user_cannot_create_chat_with_user_who_blocked_them(): void
    {
        $user = User::factory()->create();

        $user->blockedUsers()->attach($this->user->id);

        $response = $this->postJson('api/v1/chats', [
            'sender_id'   => $this->user->id,
            'receiver_id' => $user->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseEmpty('chats');

        Event::assertNotDispatched(ChatCreated::class);
    }

    public function test_user_can_delete_chat(): void
    {
        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => User::factory()->create()->id,
        ]);

        $response = $this->deleteJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        Event::assertDispatched(ChatDeleted::class, function ($event) use ($chat) {
            return $event->chat->id === $chat->id;
        });
    }

    public function test_user_cannot_delete_others_chat(): void
    {
        $users = User::factory(2)->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $users->get(0)->id,
            'receiver_id' => $users->get(1)->id,
        ]);

        $response = $this->deleteJson("/api/v1/chats/{$chat->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(ChatDeleted::class);
    }

    public function test_user_can_mark_messages_as_read(): void
    {
        $secondUser = User::factory()->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $messages = Message::factory(5)->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $secondUser->id,
            'status'           => MessageStatus::SENT->value,
        ]);

        $response = $this->patchJson("/api/v1/chats/{$chat->id}/mark-as-read");

        $response->assertStatus(Response::HTTP_OK);

        foreach ($messages as $message) {
            $this->assertDatabaseHas('messages', [
                'id'     => $message->id,
                'status' => MessageStatus::READ->value,
            ]);
        }

        Event::assertDispatched(ChatMessagesRead::class, function ($event) use ($chat) {
            return $event->chat->id === $chat->id;
        });
    }

    public function test_user_cannot_mark_other_users_messages_as_read(): void
    {
        $users = User::factory(2)->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $users->get(0)->id,
            'receiver_id' => $users->get(1)->id,
        ]);

        $response = $this->patchJson("/api/v1/chats/{$chat->id}/mark-as-read");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(ChatMessagesRead::class);
    }
}
