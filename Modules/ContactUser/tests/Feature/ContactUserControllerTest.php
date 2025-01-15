<?php

namespace Modules\ContactUser\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Modules\ContactUser\Events\ContactCreated;
use Modules\ContactUser\Events\ContactDeleted;
use Modules\ContactUser\Events\ContactUpdated;
use Modules\ContactUser\Models\ContactUser;
use Modules\ContactUser\Transformers\ContactUserResource;
use Modules\User\Models\User;
use Tests\TestCase;
use function Laravel\Prompts\confirm;

class ContactUserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'phone'      => '12345678910',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        Sanctum::actingAs($this->user);

        Event::fake();
    }

    public function test_user_can_view_all_contacts(): void
    {
        ContactUser::factory(10)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('api/v1/contacts');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(10, 'data');
        $response->assertExactJsonStructure([
            'data' => [
                '*' => [
                    'contactedUser' => [
                        'id',
                        'full_name',
                        'avatar',
                    ],
                    'created_at',
                    'updated_at',
                ]
            ]
        ]);
    }

    public function test_user_cannot_see_others_contact(): void
    {
        $users = User::factory(2)->create();

        $contact = ContactUser::factory()->create([
            'user_id'    => $this->user->id,
            'contacted_user_id' => $users->get(0),
        ]);

        Sanctum::actingAs($users->get(1));

        $response = $this->getJson("api/v1/contacts/{$contact->contacted_user_id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_user_can_see_single_contact(): void
    {
        $contact = ContactUser::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("api/v1/contacts/{$contact->contacted_user_id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id'             => $contact->contactedUser->id,
                'user_id'        => $contact->contactedUser->user_id,
                'full_name'      => $contact->contactedUser->full_name,
                'bio'            => $contact->contactedUser->bio,
                'phone'          => $contact->contactedUser->phone,
                'avatar'         => $contact->contactedUser->avatar,
                'remember_token' => $contact->contactedUser->remember_token,
                'created_at'     => $contact->contactedUser->created_at->toISOString(),
                'updated_at'     => $contact->contactedUser->updated_at->toISOString(),
            ],
        ]);
    }

    public function test_user_can_create_new_contact(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('api/v1/contacts', [
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'phone'      => $user->phone,
        ]);

        $contact = $this->user->contacts()->first()->pivot;

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('contact_user', [
            'user_id'           => $this->user->id,
            'contacted_user_id' => $user->id,
            'first_name'        => $user->first_name,
            'last_name'         => $user->last_name,
        ]);

        Event::assertDispatched(ContactCreated::class, function ($event) use ($contact) {
            return $event->contact->user_id === $contact->user_id;
        });
    }

    public function test_user_cannot_add_himself_as_a_contact(): void
    {
        $response = $this->postJson('api/v1/contacts', [
            'phone'      => $this->user->phone,
            'first_name' => $this->user->first_name,
            'last_name'  => $this->user->last_name,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => __('You cannot add yourself as a contact.')]);

        $this->assertDatabaseEmpty('contact_user');

        Event::assertNotDispatched(ContactCreated::class);
    }

    public function test_user_cannot_create_duplicate_contact(): void
    {
        $user = User::factory()->create();

        ContactUser::factory()->create([
            'user_id'    => $this->user->id,
            'contacted_user_id' => $user->id,
        ]);

        $response = $this->postJson('api/v1/contacts', [
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'phone'      => $user->phone,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment(['message' => __('Contact already exists.')]);

        $this->assertDatabaseCount('contact_user', 1);

        Event::assertNotDispatched(ContactCreated::class);
    }

    public function test_user_can_update_contact(): void
    {
        $contact = ContactUser::factory()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson("api/v1/contacts/{$contact->contacted_user_id}", [
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->assertDatabaseHas('contact_user', [
            'user_id'           => $this->user->id,
            'contacted_user_id' => $contact->contacted_user_id,
            'first_name'        => 'John',
            'last_name'         => 'Doe',
        ]);

        Event::assertDispatched(ContactUpdated::class, function ($event) use ($contact) {
            return $event->contact->id === $contact->id;
        });
    }

    public function test_user_cannot_update_others_contact(): void
    {
        $users = User::factory(2)->create();

        $contact = ContactUser::factory()->create([
            'user_id'           => $this->user->id,
            'contacted_user_id' => $users->get(0),
        ]);

        Sanctum::actingAs($users->get(1));

        $response = $this->patchJson("api/v1/contacts/{$contact->contacted_user_id}", [
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(ContactUpdated::class);
    }

    public function test_user_can_delete_contact(): void
    {
        $contact = ContactUser::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("api/v1/contacts/{$contact->contacted_user_id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('contact_user', ['id' => $contact->id]);

        Event::assertDispatched(ContactDeleted::class, function ($event) use ($contact) {
            return $event->contact->id === $contact->id;
        });
    }

    public function test_user_cannot_delete_others_contact(): void
    {
        $users = User::factory(2)->create();

        $contact = ContactUser::factory()->create([
            'user_id'           => $this->user->id,
            'contacted_user_id' => $users->get(0),
        ]);

        Sanctum::actingAs($users->get(1));

        $response = $this->deleteJson("api/v1/contacts/{$contact->contacted_user_id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        Event::assertNotDispatched(ContactDeleted::class);
    }
}
