<?php

namespace Modules\User\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\User\Events\UserBlocked;
use Modules\User\Events\UserUnblocked;
use Modules\User\Models\User;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    public function test_user_can_view_blocked_users(): void
    {
        $blockedUsers = User::factory(5)->create();

        $this->user->blockedUsers()->attach($blockedUsers->pluck('id'));

        $response = $this->getJson('/api/v1/users/blocked');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'full_name',
                    'avatar',
                ],
            ],
        ]);
    }

    public function test_user_can_block_another_user(): void
    {
        $userToBlock = User::factory()->create();

        $response = $this->postJson('/api/v1/users/block', [
            'user_id' => $userToBlock->id,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertTrue($this->user->blockedUsers->contains($userToBlock->id));

        Event::assertDispatched(UserBlocked::class, function ($event) use ($userToBlock) {
            return $event->user->id === $userToBlock->id;
        });
    }

    public function test_user_cannot_block_themselves(): void
    {
        $response = $this->postJson('/api/v1/users/block', [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'message' => __('You cannot block yourself.'),
        ]);

        $this->assertFalse($this->user->blockedUsers->contains($this->user->id));

        Event::assertNotDispatched(UserBlocked::class);
    }

    public function test_user_cannot_block_already_blocked_user(): void
    {
        $userToBlock = User::factory()->create();

        $this->user->blockedUsers()->attach($userToBlock->id);

        $response = $this->postJson('/api/v1/users/block', [
            'user_id' => $userToBlock->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'message' => __('User is already blocked.'),
        ]);

        Event::assertNotDispatched(UserBlocked::class);
    }

    public function test_user_can_unblock_another_user(): void
    {
        $userToUnblock = User::factory()->create();

        $this->user->blockedUsers()->attach($userToUnblock->id);

        $response = $this->postJson('/api/v1/users/unblock', [
            'user_id' => $userToUnblock->id,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertFalse($this->user->blockedUsers->contains($userToUnblock->id));

        Event::assertDispatched(UserUnblocked::class, function ($event) use ($userToUnblock) {
            return $event->user->id === $userToUnblock->id;
        });
    }

    public function test_user_cannot_unblock_themselves(): void
    {
        $response = $this->postJson('/api/v1/users/unblock', [
            'user_id' => $this->user->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'message' => __('You cannot unblock yourself.'),
        ]);

        Event::assertNotDispatched(UserUnblocked::class);
    }

    public function test_user_cannot_unblock_non_blocked_user(): void
    {
        $userToUnblock = User::factory()->create();

        $response = $this->postJson('/api/v1/users/unblock', [
            'user_id' => $userToUnblock->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'message' => __('User is not blocked.'),
        ]);

        Event::assertNotDispatched(UserUnblocked::class);
    }
}
