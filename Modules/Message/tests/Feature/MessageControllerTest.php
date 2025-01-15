<?php

namespace Modules\Message\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Events\MessageDeleted;
use Modules\Message\Events\MessageUpdated;
use Modules\Message\Events\MessageCreated;
use Modules\Message\Models\Message;
use Modules\Message\Notifications\NewMessage;
use Modules\User\Models\User;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Chat $chat;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->chat  = Chat::factory()->create(['sender_id' => $this->user->id]);
        $this->group = Group::factory()->create();
        $this->group->members()->attach($this->user->id);
        $this->group->members()->attach(User::factory(10)->create()->pluck('id'));

        Sanctum::actingAs($this->user);

        Event::fake();
        Notification::fake();
    }

    public function test_user_can_create_message_in_chat(): void
    {
        $payload = [
            'messageable_type' => 'chat',
            'messageable_id'   => $this->chat->id,
            'content'          => 'Hello :D',
        ];

        $response = $this->postJson('api/v1/messages', $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $message = $response->json('data.message');

        $this->assertDatabaseHas('messages', [
            'id'               => $message['id'],
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
            'content'          => $payload['content'],
        ]);
        $this->assertDatabaseHas('messages', [
            'id'     => $message['id'],
            'status' => MessageStatus::SENT->value,
        ]);

        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            return $event->message->id === $message['id'];
        });
        Notification::assertSentTo(
            $this->chat->receiver,
            NewMessage::class,
            function ($notification) use ($message) {
                return $notification->message->id === $message['id'];
            }
        );
    }

    public function test_user_can_create_message_in_group(): void
    {
        $payload = [
            'messageable_type' => 'group',
            'messageable_id'   => $this->group->id,
            'content'          => 'Hello Group!',
        ];

        $response = $this->postJson('api/v1/messages', $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $message = $response->json('data.message');

        $this->assertDatabaseHas('messages', [
            'id'               => $message['id'],
            'messageable_id'   => $this->group->id,
            'messageable_type' => Group::class,
            'sender_id'        => $this->user->id,
            'content'          => $payload['content'],
        ]);
        $this->assertDatabaseHas('messages', [
            'id'     => $message['id'],
            'status' => MessageStatus::SENT->value,
        ]);

        $groupMembers = $this->group->members()->where('member_id', '!=', $this->user->id)->get();

        Event::assertDispatched(MessageCreated::class, function ($event) use ($message) {
            return $event->message->id === $message['id'];
        });
        Notification::assertSentTo(
            $groupMembers,
            NewMessage::class,
            function ($notification) use ($message) {
                return $notification->message->id === $message['id'];
            }
        );
    }

    public function test_user_cannot_send_message_to_blocked_user(): void
    {
        $blockedUser = User::factory()->create();
        $this->user->blockedUsers()->attach($blockedUser->id);

        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $blockedUser->id,
        ]);

        $response = $this->postJson('/api/v1/messages', [
            'messageable_id'   => $chat->id,
            'messageable_type' => 'chat',
            'content'          => 'Hello',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseEmpty('messages');

        Event::assertNotDispatched(MessageCreated::class);
    }

    public function test_user_cannot_send_message_to_user_who_blocked_them(): void
    {
        $blockingUser = User::factory()->create();
        $blockingUser->blockedUsers()->attach($this->user->id);

        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $blockingUser->id,
        ]);

        $response = $this->postJson('/api/v1/messages', [
            'messageable_id'   => $chat->id,
            'messageable_type' => 'chat',
            'content'          => 'Hi!',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseEmpty('messages');

        Event::assertNotDispatched(MessageCreated::class);
    }

    public function test_user_cannot_create_message_with_invalid_messageable_type(): void
    {
        $payload = [
            'messageable_type' => 'invalid_type',
            'messageable_id'   => $this->chat->id,
            'content'          => 'Hello :D',
        ];

        $response = $this->postJson('api/v1/messages', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['messageable_type']);

        Event::assertNotDispatched(MessageCreated::class);
        Notification::assertNotSentTo($this->chat->receiver, NewMessage::class);
    }

    public function test_user_cannot_create_message_with_invalid_messageable_id(): void
    {
        $payload = [
            'messageable_type' => 'chat',
            'messageable_id'   => 'invalid_id',
            'content'          => 'Hello :D',
        ];

        $response = $this->postJson('api/v1/messages', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors(['messageable_id']);

        Event::assertNotDispatched(MessageCreated::class);
        Notification::assertNotSentTo($this->chat->receiver, NewMessage::class);
    }

    public function test_user_cannot_create_message_with_non_existent_messageable_id(): void
    {
        $payload = [
            'messageable_type' => 'chat',
            'messageable_id'   => 9999,
            'content'          => 'Hello :D',
        ];

        $response = $this->postJson('api/v1/messages', $payload);

        $response->assertStatus(Response::HTTP_NOT_FOUND);

        Event::assertNotDispatched(MessageCreated::class);
        Notification::assertNotSentTo($this->chat->receiver, NewMessage::class);
    }

    public function test_user_cannot_create_message_with_invalid_content(): void
    {
        $testCases = [
            'empty_content' => [
                'messageable_type' => 'chat',
                'messageable_id'   => $this->chat->id,
                'content'          => '',
            ],
            'long_content' => [
                'messageable_type' => 'chat',
                'messageable_id'   => $this->chat->id,
                'content'          => str_repeat('a', 256),
            ],
        ];

        foreach ($testCases as $testCase => $payload) {
            $response = $this->postJson('api/v1/messages', $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->assertJsonValidationErrors(['content']);
            $this->assertDatabaseEmpty('messages');

            Event::assertNotDispatched(MessageCreated::class);
            Notification::assertNotSentTo($this->chat->receiver, NewMessage::class);
        }
    }

    public function test_user_cannot_create_message_in_group_if_not_a_member(): void
    {
        $group = Group::factory()->create();

        $payload = [
            'messageable_type' => 'group',
            'messageable_id'   => $group->id,
            'content'          => 'Hello Group!',
        ];

        $response = $this->postJson('api/v1/messages', $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(MessageCreated::class);
        Notification::assertNotSentTo($this->chat->receiver, NewMessage::class);
    }

    public function test_user_cannot_create_message_if_chat_does_not_belong_to_him(): void
    {
        $users = User::factory(2)->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $users->get(0)->id,
            'receiver_id' => $users->get(1)->id,
        ]);

        $payload = [
            'messageable_type' => 'chat',
            'messageable_id'   => $chat->id,
            'content'          => 'Bye D:',
        ];

        $response = $this->postJson('api/v1/messages', $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(MessageCreated::class);
        Notification::assertNotSentTo($this->chat->receiver, NewMessage::class);
    }

    public function test_user_can_update_message(): void
    {
        $message = Message::factory()->create([
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
            'status'           => MessageStatus::SENT->value,
        ]);

        $content = 'Hello :D';

        $response = $this->patchJson("api/v1/messages/{$message->id}", ['content' => $content]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id'               => $message->id,
                'messageable_id'   => $this->chat->id,
                'messageable_type' => Chat::class,
                'sender_id'        => $this->user->id,
                'content'          => $content,
            ]
        ]);

        $this->assertDatabaseHas('messages', [
            'id'               => $message->id,
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
            'content'          => $content,
        ]);

        Event::assertDispatched(MessageUpdated::class, function ($event) use ($message, $content) {
            return $event->message->id === $message->id
                && $event->message->content === $content;
        });
    }

    public function test_user_cannot_update_failed_message(): void
    {
        $failedMessage = Message::factory()->create([
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
            'status'           => MessageStatus::FAILED->value,
        ]);

        $content = 'Bye D:';
        $response = $this->patchJson("api/v1/messages/{$failedMessage->id}", ['content' => $content]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing('messages', [
            'id'               => $failedMessage->id,
            'content'          => $content,
        ]);

        Event::assertNotDispatched(MessageUpdated::class);
    }

    public function test_user_cannot_update_pending_message(): void
    {
        $pendingMessage = Message::factory()->create([
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
            'status'           => MessageStatus::PENDING->value,
        ]);

        $content = 'Bye D:';
        $response = $this->patchJson("api/v1/messages/{$pendingMessage->id}", ['content' => $content]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseMissing('messages', [
            'id'               => $pendingMessage->id,
            'content'          => $content,
        ]);

        Event::assertNotDispatched(MessageUpdated::class);
    }


    public function test_user_cannot_update_others_message(): void
    {
        $message = Message::factory()->create([
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $content = 'Hello :D';

        $response = $this->patchJson("api/v1/messages/{$message->id}", ['content' => $content]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('messages', [
            'id'               => $message->id,
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $user->id,
            'content'          => $content,
        ]);

        Event::assertNotDispatched(MessageUpdated::class);
    }

    public function test_user_can_delete_message(): void
    {
        $message = Message::factory()->create([
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
        ]);

        $response = $this->deleteJson("api/v1/messages/{$message->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('messages', ['id' => $message->id]);

        Event::assertDispatched(MessageDeleted::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    }

    public function test_user_cannot_delete_others_message(): void
    {
        $message = Message::factory()->create([
            'messageable_id'   => $this->chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $this->user->id,
        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson("api/v1/messages/{$message->id}", ['content' => 'Bye D:']);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('messages', ['id' => $message->id]);

        Event::assertNotDispatched(MessageDeleted::class);
    }
}
