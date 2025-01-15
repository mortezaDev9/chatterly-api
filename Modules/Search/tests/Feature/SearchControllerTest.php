<?php

namespace Modules\Search\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Laravel\Sanctum\Sanctum;
use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\User\Models\User;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);
    }

    public function test_user_can_search_chats(): void
    {
        $secondUser = User::factory()->create();

        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $response = $this->getJson("/api/v1/search?q={$secondUser->first_name}");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([
            'data' => [
                'chats' => [
                    [
                        'id'            => $chat->id,
                        'sender_id'     => $chat->sender_id,
                        'receiver_id'   => $chat->receiver_id,
                        'user' => [
                            'id'        => $secondUser->id,
                            'full_name' => $secondUser->full_name,
                            'avatar'    => $secondUser->avatar,
                        ],
                        'latestMessage' => null,
                        'created_at'    => $chat->created_at->toIsoString(),
                        'updated_at'    => $chat->updated_at->toIsoString(),
                    ],
                ],
            ],
        ]);
    }

    public function test_user_cannot_get_others_chats_by_search(): void
    {
        $users = User::factory(2)->create();

        Chat::factory()->create([
            'sender_id'   => $users->get(0)->id,
            'receiver_id' => $users->get(1)->id,
        ]);

        $response = $this->getJson("/api/v1/search?q={$users->get(1)->first_name}");

        $response->assertJson(['data' => []]);
    }

    public function test_no_chat_or_group_returned_on_empty_search_query(): void
    {
        Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => User::factory()->create()->id,
        ]);

        Group::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->getJson("/api/v1/search?q=");

        $response->assertJson(['data' => []]);
    }

    public function test_no_chat_returned_when_search_query_is_user_s_name(): void
    {
        $testCases = [
            'first_name' => $this->user->first_name,
            'last_name'  => $this->user->last_name,
            'full_name'  => $this->user->full_name,
        ];

        foreach ($testCases as $query) {
            $response = $this->getJson("/api/v1/search?q={$query}");

            $response->assertJson(['data' => []]);
        }
    }

    public function test_user_can_search_group_by_name(): void
    {
        $group = Group::factory()->create(['owner_id' => $this->user->id]);

        $group->members()->attach($this->user);

        $response = $this->getJson("/api/v1/search?q={$group->name}");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([
            'data' => [
                'groups' => [
                    [
                        'id'            => $group->id,
                        'name'          => $group->name,
                        'picture'       => $group->picture,
                        'sender'        => null,
                        'latestMessage' => null,
                        'created_at'    => $group->created_at->toISOString(),
                        'updated_at'    => $group->updated_at->toISOString(),
                    ],
                ],
            ],
        ]);
    }

    public function test_user_can_search_groups_they_are_not_a_member_of(): void
    {
        $group = Group::factory()->create(['owner_id' => User::factory()->create()->id]);

        $response = $this->getJson("/api/v1/search?q={$group->name}");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([
            'data' => [
                'groups' => [
                    [
                        'id'            => $group->id,
                        'name'          => $group->name,
                        'picture'       => $group->picture,
                        'sender'        => null,
                        'latestMessage' => null,
                        'created_at'    => $group->created_at->toISOString(),
                        'updated_at'    => $group->updated_at->toISOString(),
                    ],
                ],
            ],
        ]);
    }

    public function test_user_can_search_both_chats_and_groups(): void
    {
        $secondUser = User::factory()->create(['first_name' => 'Laravel']);

        $chat = Chat::factory()->create([
            'sender_id'   => $this->user->id,
            'receiver_id' => $secondUser->id,
        ]);

        $group = Group::factory()->create([
            'owner_id' => $this->user->id,
            'name'     => 'Laravel Developers',
        ]);

        $group->members()->attach($this->user);

        $response = $this->getJson("/api/v1/search?q=Laravel");

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJson([
            'data' => [
                'chats' => [
                    [
                        'id'            => $chat->id,
                        'sender_id'     => $chat->sender_id,
                        'receiver_id'   => $chat->receiver_id,
                        'user' => [
                            'id'        => $secondUser->id,
                            'full_name' => $secondUser->full_name,
                            'avatar'    => $secondUser->avatar,
                        ],
                        'latestMessage' => null,
                        'created_at'    => $chat->created_at->toIsoString(),
                        'updated_at'    => $chat->updated_at->toIsoString(),
                    ],
                ],
                'groups' => [
                    [
                        'id'            => $group->id,
                        'name'          => $group->name,
                        'picture'       => $group->picture,
                        'sender'        => null,
                        'latestMessage' => null,
                        'created_at'    => $group->created_at->toISOString(),
                        'updated_at'    => $group->updated_at->toISOString(),
                    ],
                ],
            ],
        ]);
    }
}
