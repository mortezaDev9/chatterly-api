<?php

namespace Modules\Group\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Group\Events\GroupCreated;
use Modules\Group\Events\GroupDeleted;
use Modules\Group\Events\GroupMessagesRead;
use Modules\Group\Events\OwnershipTransferred;
use Modules\Group\Events\GroupUpdated;
use Modules\Group\Events\UserJoinedGroup;
use Modules\Group\Events\UserLeftGroup;
use Modules\Group\Models\Group;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;
use Modules\User\Models\User;
use Tests\TestCase;

class GroupControllerTest extends TestCase
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

    public function test_user_can_view_all_groups(): void
    {
        $groups = Group::factory(10)->create();

        foreach ($groups as $group) {
            $group->members()->attach($this->user->id);
        }

        $response = $this->getJson('api/v1/groups');

        $response->assertJsonCount(10, 'data');
        $response->assertExactJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'picture',
                    'sender',
                    'latestMessage',
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }

    public function test_user_cannot_view_groups_they_do_not_belong_to(): void
    {
        Group::factory(10)->create();

        $response = $this->getJson('api/v1/groups');

        $response->assertJson(['data' => []]);
    }

    public function test_user_can_view_group(): void
    {
        $group = Group::factory()->create();

        $response = $this->getJson("/api/v1/groups/{$group->id}");

        $response->assertJson([
            'data' => [
                'id'          => $group->id,
                'group_id'    => $group->group_id,
                'owner_id'    => $group->owner_id,
                'name'        => $group->name,
                'picture'     => $group->picture,
                'description' => $group->description,
                'members'     => [],
                'messages'    => [],
                'created_at'  => $group->created_at->toISOString(),
                'updated_at'  => $group->updated_at->toISOString(),
            ]
        ]);
    }

    public function test_user_can_view_group_with_data(): void
    {
        $group = Group::factory()->create();
        $group->members()->attach($this->user->id);

        $member = $group->members()->whereMemberId($this->user->id)->first();
        $member->setRelation('user', $this->user);

        $message = Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => $this->user->id,
            'status'           => MessageStatus::SENT->value,
        ]);

        $response = $this->getJson("/api/v1/groups/{$group->id}");

        $response->assertJson([
            'data' => [
                'id'          => $group->id,
                'group_id'    => $group->group_id,
                'owner_id'    => $group->owner_id,
                'name'        => $group->name,
                'picture'     => $group->picture,
                'description' => $group->description,
                'members'     => [
                    [
                        'id'             => $member->user->id,
                        'user_id'        => $member->user->user_id,
                        'full_name'      => $member->user->full_name,
                        'bio'            => $member->user->bio,
                        'phone'          => $member->user->phone,
                        'avatar'         => $member->user->avatar,
                        'remember_token' => $member->user->remember_token,
                        'created_at'     => $member->user->created_at->toISOString(),
                        'updated_at'     => $member->user->updated_at->toISOString(),
                    ]
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
                    ]
                ],
                'created_at'  => $group->created_at->toISOString(),
                'updated_at'  => $group->updated_at->toISOString(),
            ]
        ]);
    }

    public function test_unread_messages_marked_as_read_when_user_view_group(): void
    {
        $group = Group::factory()->create();
        $group->members()->attach($this->user->id);

        $unreadMessages = Message::factory(3)->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => User::factory()->create()->id,
        ]);

        $response = $this->getJson("/api/v1/groups/{$group->id}");

        $response->assertStatus(Response::HTTP_OK);

        foreach ($unreadMessages as $message) {
            $this->assertDatabaseHas('message_member', [
                'message_id' => $message->id,
                'member_id'  => $this->user->id,
            ]);
        }
    }

    public function test_user_does_not_receive_failed_or_pending_messages()
    {
        $group = Group::factory()->create();
        $group->members()->attach($this->user->id);

        $failedMessage = Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => User::factory()->create()->id,
            'status'           => MessageStatus::FAILED->value,
        ]);

        $pendingMessage = Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => User::factory()->create()->id,
            'status'           => MessageStatus::PENDING->value,
        ]);

        $response = $this->getJson("/api/v1/groups/{$group->id}");

        $response->assertJsonMissing(['messages.id' => $failedMessage->id]);
        $response->assertJsonMissing(['messages.id' => $pendingMessage->id]);
    }

    public function test_user_can_create_group(): void
    {
        $payload = [
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1'
        ];

        $response = $this->postJson('api/v1/groups', $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $group = $response->json('data');

        $this->assertDatabaseCount('groups', 1);
        $this->assertDatabaseHas('groups', [
            'id'          => $group['id'],
            'group_id'    => $payload['group_id'],
            'owner_id'    => $this->user->id,
            'name'        => $payload['name'],
            'picture'     => null,
            'description' => $payload['description'],
        ]);

        $this->assertDatabaseCount('group_member', 1);
        $this->assertDatabaseHas('group_member', [
            'group_id'  => $group['id'],
            'member_id' => $this->user->id,
            'is_admin'  => true,
        ]);

        Event::assertDispatched(GroupCreated::class, function ($event) use ($group) {
            return $event->group->id === $group['id'];
        });
    }

    public function test_user_cannot_create_group_with_invalid_data(): void
    {
        Group::factory()->create(['group_id' => 'LaravelDevs']);

        $testCases = [
            'missing_group_id'    => [
                'group_id'    => '',
                'name'        => 'Group 1',
                'description' => 'Description 1',
            ],
            'missing_name'        => [
                'group_id'    => 'group-1',
                'name'        => '',
                'description' => 'Description 1',
            ],
            'long_group_id'       => [
                'group_id'    => str_repeat('a', 256),
                'name'        => 'Group 1',
                'description' => 'Description 1',
            ],
            'long_name'           => [
                'group_id'    => 'group-1',
                'name'        => str_repeat('a', 256),
                'description' => 'Description 1',
            ],
            'invalid_group_id'    => [
                'group_id'    => 12345,
                'name'        => 'Group 1',
                'description' => 'Description 1',
            ],
            'non_unique_group_id' => [
                'group_id'    => 'LaravelDevs',
                'name'        => 'Group 2',
                'description' => 'Description 2',
            ],
        ];

        foreach ($testCases as $testCase => $payload) {
            $response = $this->postJson('api/v1/groups', $payload);

            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

            $this->assertDatabaseCount('groups', 1);
            $this->assertDatabaseCount('group_member', 0);

            Event::assertNotDispatched(GroupCreated::class);
        }
    }

    public function test_group_owner_can_update_group(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => $this->user->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $group->members()->attach($this->user->id);

        $payload = [
            'name'        => 'Updated Group Name',
            'description' => 'Updated Description',
        ];

        $response = $this->patchJson("api/v1/groups/{$group->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('groups', [
            'id'          => $group->id,
            'name'        => $payload['name'],
            'description' => $payload['description'],
        ]);

        Event::assertDispatched(GroupUpdated::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_group_admin_cannot_update_group(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $group->members()->attach($this->user->id, ['is_admin' => true]);

        $payload = [
            'name'        => 'Attempted Update',
            'description' => 'Attempted Update Description',
        ];

        $response = $this->patchJson("api/v1/groups/{$group->id}", $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'group_id' => $group->group_id
        ]);

        Event::assertNotDispatched(GroupUpdated::class);
    }

    public function test_group_member_cannot_update_group(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $group->members()->attach($this->user->id);

        $payload = [
            'name'        => 'Attempted Update',
            'description' => 'Attempted Update Description',
        ];

        $response = $this->patchJson("api/v1/groups/{$group->id}", $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'group_id' => $group->group_id
        ]);

        Event::assertNotDispatched(GroupUpdated::class);
    }

    public function test_user_cannot_update_group_they_do_not_own(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $payload = [
            'name'        => 'Attempted Update',
            'description' => 'Attempted Update Description',
        ];

        $response = $this->patchJson("api/v1/groups/{$group->id}", $payload);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'group_id' => $group->group_id
        ]);

        Event::assertNotDispatched(GroupUpdated::class);
    }

    public function test_group_owner_can_delete_group(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => $this->user->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $group->members()->attach($this->user->id);

        $response = $this->deleteJson("api/v1/groups/{$group->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('groups', [
            'id'       => $group->id,
            'group_id' => $group->group_id
        ]);

        Event::assertDispatched(GroupDeleted::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_group_admin_cannot_delete_group(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $group->members()->attach($this->user->id, ['is_admin' => true]);

        $response = $this->deleteJson("api/v1/groups/{$group->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'group_id' => $group->group_id
        ]);

        Event::assertNotDispatched(GroupDeleted::class);
    }

    public function test_group_member_cannot_delete_group(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $group->members()->attach($this->user->id);

        $response = $this->deleteJson("api/v1/groups/{$group->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'group_id' => $group->group_id
        ]);

        Event::assertNotDispatched(GroupDeleted::class);
    }

    public function test_user_cannot_delete_group_they_do_not_own(): void
    {
        $group = Group::factory()->create([
            'owner_id'    => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $response = $this->deleteJson("api/v1/groups/{$group->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'group_id' => $group->group_id
        ]);

        Event::assertNotDispatched(GroupDeleted::class);
    }

    public function test_user_can_join_a_group(): void
    {
        $group = Group::factory()->create();

        $response = $this->postJson('/api/v1/groups/join', ['group_id' => $group->id]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $group->id,
            'member_id' => $this->user->id,
        ]);

        Event::assertDispatched(UserJoinedGroup::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_unread_messages_marked_as_read_when_user_joins_group(): void
    {
        $group = Group::factory()->create();

        $unreadMessages = Message::factory(3)->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => User::factory()->create()->id,
        ]);

        $response = $this->postJson('/api/v1/groups/join', ['group_id' => $group->id]);

        $response->assertStatus(Response::HTTP_CREATED);

        foreach ($unreadMessages as $message) {
            $this->assertDatabaseHas('message_member', [
                'message_id' => $message->id,
                'member_id'  => $this->user->id,
            ]);
        }
    }

    public function test_user_cannot_join_a_group_they_are_already_a_member_of(): void
    {
        $group = Group::factory()->create();
        $group->members()->attach($this->user->id);

        $response = $this->postJson('/api/v1/groups/join', ['group_id' => $group->id]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Event::assertNotDispatched(UserJoinedGroup::class);
    }

    public function test_user_can_leave_a_group(): void
    {
        $group = Group::factory()->create(['owner_id' => User::factory()->create()]);
        $group->members()->attach($this->user->id);

        $response = $this->deleteJson("/api/v1/groups/{$group->id}/leave");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $group->id,
            'member_id' => $this->user->id,
        ]);

        Event::assertDispatched(UserLeftGroup::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_user_cannot_leave_a_group_they_are_not_a_member_of(): void
    {
        $group = Group::factory()->create();

        $response = $this->deleteJson("/api/v1/groups/{$group->id}/leave");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Event::assertNotDispatched(UserLeftGroup::class);
    }

    public function test_owner_cannot_leave_group(): void
    {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $group->members()->attach($this->user->id);

        $response = $this->deleteJson("/api/v1/groups/{$group->id}/leave");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $group->id,
            'member_id' => $this->user->id,
        ]);

        Event::assertNotDispatched(UserLeftGroup::class);
    }

    public function test_owner_can_leave_group_if_they_transfer_ownership(): void
    {
        $user  = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $group->members()->attach([$this->user->id, $user->id]);

        $this->patchJson("/api/v1/groups/{$group->id}/transfer-ownership/{$user->id}");

        $response = $this->deleteJson("/api/v1/groups/{$group->id}/leave");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'owner_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $group->id,
            'member_id' => $this->user->id,
        ]);

        Event::assertDispatched(UserLeftGroup::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_owner_can_transfer_ownership_to_another_member(): void
    {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);
        $user  = User::factory()->create();
        $group->members()->attach($user->id);

        $response = $this->patchJson("/api/v1/groups/{$group->id}/transfer-ownership/{$user->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'owner_id' => $user->id,
        ]);

        Event::assertDispatched(OwnershipTransferred::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_owner_cannot_transfer_ownership_to_a_non_existent_member(): void
    {
        $user  = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->patchJson("/api/v1/groups/{$group->id}/transfer-ownership/{$user->id}");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseHas('groups', [
            'id'       => $group->id,
            'owner_id' => $this->user->id,
        ]);

        Event::assertNotDispatched(OwnershipTransferred::class);
    }

    public function test_owner_cannot_transfer_ownership_to_themselves(): void
    {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->patchJson("/api/v1/groups/{$group->id}/transfer-ownership/{$this->user->id}");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Event::assertNotDispatched(OwnershipTransferred::class);
    }

    public function test_user_can_mark_messages_as_read(): void
    {
        $group = Group::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        $group->members()->attach($this->user->id);

        Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => User::factory()->create()->id,
            'status'           => MessageStatus::SENT->value,
        ]);

        $response = $this->postJson('/api/v1/groups/mark-as-read', ['group_id' => $group->id]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('message_member', [
            'message_id' => $group->messages->first()->id,
            'member_id'  => $this->user->id,
        ]);

        Event::assertDispatched(GroupMessagesRead::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_mark_as_read_only_marks_messages_as_read_for_current_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $group = Group::factory()->create();

        $group->members()->attach([$user1->id, $user2->id]);

        $messages = Message::factory(3)->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => $user2->id,
        ]);

        $this->actingAs($user1);

        $response = $this->postJson('/api/v1/groups/mark-as-read', ['group_id' => $group->id]);

        $response->assertStatus(Response::HTTP_OK);

        foreach ($messages as $message) {
            $this->assertTrue($message->readers()->whereMemberId($user1->id)->exists());
            $this->assertFalse($message->readers()->whereMemberId($user2->id)->exists());
        }

        Event::assertDispatched(GroupMessagesRead::class, function ($event) use ($group) {
            return $event->group->id === $group->id;
        });
    }

    public function test_user_cannot_mark_messages_as_read_in_group_they_do_not_belong_to(): void
    {
        $group = Group::factory()->create([
            'owner_id' => User::factory()->create()->id,
            'group_id'    => 'group-1',
            'name'        => 'Group 1',
            'description' => 'Description 1',
        ]);

        Message::factory()->create([
            'messageable_id'   => $group->id,
            'messageable_type' => Group::class,
            'sender_id'        => User::factory()->create()->id,
            'status'           => MessageStatus::SENT->value,
        ]);

        $response = $this->postJson('/api/v1/groups/mark-as-read', ['group_id' => $group->id]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('messages', [
            'id'     => $group->messages->first()->id,
            'status' => MessageStatus::READ->value,
        ]);

        Event::assertNotDispatched(OwnershipTransferred::class);
    }
}
