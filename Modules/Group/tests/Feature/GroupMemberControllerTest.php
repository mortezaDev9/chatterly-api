<?php

namespace Modules\Group\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\Group\Events\UserAddedToGroup;
use Modules\Group\Events\UserRemovedFromGroup;
use Modules\Group\Events\UserPromotedToAdmin;
use Modules\Group\Models\Group;
use Modules\User\Models\User;
use Tests\TestCase;

class GroupMemberControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->group = Group::factory()->create(['owner_id' => User::factory()->create()->id]);

        Sanctum::actingAs($this->user);

        Event::fake();
    }

    public function test_user_can_view_group_members(): void
    {
        $this->group->members()
            ->attach(User::factory(10)->create()
                ->pluck('id')
                ->toArray()
            );

        $response = $this->getJson("api/v1/groups/{$this->group->id}/members");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(10, 'data');
        $response->assertExactJsonStructure([
            'data' => [
                '*' => [
                    'group_id',
                    'member' => [
                        'id',
                        'full_name',
                        'avatar',
                    ],
                    'is_admin',
                    'joined_at',
                ],
            ],
        ]);
    }

    public function test_user_can_add_member_to_group(): void
    {
        $user = User::factory()->create();

        $this->group->members()->attach($this->user->id);

        $response = $this->postJson("api/v1/groups/{$this->group->id}/members", ['user_id' => $user->id]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
        ]);

        Event::assertDispatched(UserAddedToGroup::class, function ($event) {
            return $event->group->id === $this->group->id;
        });
    }

    public function test_user_cannot_add_member_to_group_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson("api/v1/groups/{$this->group->id}/members", ['user_id' => $user->id]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
        ]);

        Event::assertNotDispatched(UserAddedToGroup::class);
    }

    public function test_user_cannot_add_themselves_as_member_to_group_they_are_already_a_member_of(): void
    {
        $this->group->members()->attach($this->user->id);

        $response = $this->postJson("api/v1/groups/{$this->group->id}/members", ['user_id' => $this->user->id]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseCount('group_member', 1);

        Event::assertNotDispatched(UserAddedToGroup::class);
    }

    public function test_user_cannot_add_duplicate_member_to_group(): void
    {
        $user = User::factory()->create();

        $this->group->members()->attach([$this->user->id, $user->id]);

        $response = $this->postJson("api/v1/groups/{$this->group->id}/members", ['user_id' => $user->id]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseCount('group_member', 2);

        Event::assertNotDispatched(UserAddedToGroup::class);
    }

    public function test_owner_can_remove_member_from_group(): void
    {
        $user = User::factory()->create();

        $this->group->update(['owner_id' => $this->user->id]);
        $this->group->members()->attach([$this->user->id, $user->id]);

        $response = $this->deleteJson("api/v1/groups/{$this->group->id}/members/{$user->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
        ]);

        Event::assertDispatched(UserRemovedFromGroup::class, function ($event) {
            return $event->group->id === $this->group->id;
        });
    }

    public function test_admin_can_remove_member_from_group(): void
    {
        $user = User::factory()->create();

        $this->group->members()->attach($this->user->id, ['is_admin' => true]);
        $this->group->members()->attach($user->id);

        $response = $this->deleteJson("api/v1/groups/{$this->group->id}/members/{$user->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
        ]);

        Event::assertDispatched(UserRemovedFromGroup::class, function ($event) {
            return $event->group->id === $this->group->id;
        });
    }

    public function test_admin_cannot_remove_owner_from_group(): void
    {
        $owner = User::factory()->create();

        $this->group->update(['owner_id' => $owner->id]);

        $this->group->members()->attach([$owner->id, $this->user->id], ['is_admin' => true]);

        $response = $this->deleteJson("api/v1/groups/{$this->group->id}/members/{$this->group->owner_id}");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $this->group->owner_id,
        ]);

        Event::assertNotDispatched(UserRemovedFromGroup::class);
    }

    public function test_admin_cannot_remove_admin_from_group(): void
    {
        $admin = User::factory()->create();

        $this->group->members()->attach([$admin->id, $this->user->id], ['is_admin' => true]);

        $response = $this->deleteJson("api/v1/groups/{$this->group->id}/members/{$admin->id}");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $admin->id,
        ]);

        Event::assertNotDispatched(UserRemovedFromGroup::class);
    }

    public function test_member_cannot_remove_member_from_group(): void
    {
        $user = User::factory()->create();

        $this->group->members()->attach($this->user->id);
        $this->group->members()->attach($user->id);

        $response = $this->deleteJson("api/v1/groups/{$this->group->id}/members/{$user->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
        ]);

        Event::assertNotDispatched(UserRemovedFromGroup::class);
    }

    public function test_user_cannot_remove_member_from_group_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();

        $this->group->members()->attach($user->id, ['is_admin' => true]);

        $response = $this->deleteJson("api/v1/groups/{$this->group->id}/members/{$user->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
        ]);

        Event::assertNotDispatched(UserRemovedFromGroup::class);
    }

    public function test_owner_can_update_group_member_role_to_admin(): void
    {
        $user = User::factory()->create();

        $this->group->update(['owner_id' => $this->user->id]);
        $this->group->members()->attach([$this->user->id, $user->id]);

        $response = $this->patchJson("api/v1/groups/{$this->group->id}/members/{$user->id}/promote-to-admin");

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
            'is_admin'  => true,
        ]);

        Event::assertDispatched(UserPromotedToAdmin::class, function ($event) {
            return $event->group->id === $this->group->id;
        });
    }

    public function test_owner_cannot_update_group_member_role_to_admin_if_member_is_already_an_admin(): void
    {
        $user = User::factory()->create();

        $this->group->update(['owner_id' => $this->user->id]);
        $this->group->members()->attach([$this->user->id, $user->id], ['is_admin' => true]);

        $response = $this->patchJson("api/v1/groups/{$this->group->id}/members/{$user->id}/promote-to-admin");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        Event::assertNotDispatched(UserPromotedToAdmin::class);
    }

    public function test_admin_cannot_update_group_member_role_to_admin(): void
    {
        $user = User::factory()->create();

        $this->group->members()->attach($this->user->id, ['is_admin' => true]);
        $this->group->members()->attach($user->id);

        $response = $this->patchJson("api/v1/groups/{$this->group->id}/members/{$user->id}/promote-to-admin");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
            'is_admin'  => true,
        ]);

        Event::assertNotDispatched(UserPromotedToAdmin::class);
    }

    public function test_member_cannot_update_group_member_role_to_admin(): void
    {
        $user = User::factory()->create();

        $this->group->members()->attach($this->user->id);
        $this->group->members()->attach($user->id);

        $response = $this->patchJson("api/v1/groups/{$this->group->id}/members/{$user->id}/promote-to-admin");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
            'is_admin'  => true,
        ]);

        Event::assertNotDispatched(UserPromotedToAdmin::class);
    }

    public function test_owner_cannot_update_group_member_role_from_group_they_do_not_belong_to(): void
    {
        $user = User::factory()->create();

        $this->group->update(['owner_id' => $this->user->id]);
        $this->group->members()->attach($user->id);

        $response = $this->patchJson("api/v1/groups/{$this->group->id}/members/{$user->id}/promote-to-admin");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('group_member', [
            'group_id'  => $this->group->id,
            'member_id' => $user->id,
            'is_admin'  => true,
        ]);

        Event::assertNotDispatched(UserPromotedToAdmin::class);
    }
}
