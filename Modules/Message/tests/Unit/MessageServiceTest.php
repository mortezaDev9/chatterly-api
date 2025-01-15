<?php

namespace Modules\Message\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;
use Modules\Message\Services\MessageService;
use Modules\User\Models\User;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private MessageService $messageService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->messageService = new MessageService();
    }

    public function test_it_marks_group_messages_as_read(): void
    {
        $group     = Group::factory()->create();
        $otherUser = User::factory()->create();

        $message1 = Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => $otherUser->id,
        ]);
        $message2 = Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => $otherUser->id,
        ]);

        $this->messageService->markGroupMessagesAsRead($group);

        $this->assertTrue($this->user->readMessages->contains($message1->id));
        $this->assertTrue($this->user->readMessages->contains($message2->id));
    }

    public function test_it_marks_chat_messages_as_read(): void
    {
        $otherUser = User::factory()->create();

        $chat = Chat::factory()->create(['sender_id' => $this->user->id, 'receiver_id' => $otherUser->id]);

        $message1 = Message::factory()->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $otherUser->id,
            'status'           => MessageStatus::SENT->value,
        ]);
        $message2 = Message::factory()->create([
            'messageable_id'   => $chat->id,
            'messageable_type' => Chat::class,
            'sender_id'        => $otherUser->id,
            'status'           => MessageStatus::SENT->value,
        ]);

        $this->messageService->markChatMessagesAsRead($chat);

        $this->assertDatabaseHas('messages', [
            'id'     => $message1->id,
            'status' => MessageStatus::READ->value,
        ]);
        $this->assertDatabaseHas('messages', [
            'id'     => $message2->id,
            'status' => MessageStatus::READ->value,
        ]);
    }

    public function test_it_does_not_mark_messages_as_read_if_sent_by_authenticated_user(): void
    {
        $group = Group::factory()->create();

        $message = Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => $this->user->id,
        ]);

        $this->messageService->markGroupMessagesAsRead($group);

        $this->assertFalse($this->user->readMessages->contains($message->id));
    }

    public function test_it_does_not_mark_messages_as_read_if_already_read(): void
    {
        $group     = Group::factory()->create();
        $otherUser = User::factory()->create();

        $message = Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => $otherUser->id,
        ]);

        $this->user->readMessages()->attach($message->id);

        $this->messageService->markGroupMessagesAsRead($group);

        $this->assertCount(1, $this->user->readMessages);
    }

    public function test_it_handles_empty_group_or_chat(): void
    {
        $group = Group::factory()->create();
        $chat  = Chat::factory()->create();

        $this->messageService->markGroupMessagesAsRead($group);
        $this->messageService->markChatMessagesAsRead($chat);

        $this->assertCount(0, $this->user->readMessages);
        $this->assertDatabaseMissing('messages', [
            'status' => MessageStatus::READ->value,
        ]);
    }
}
