<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUuidIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_accounts_receive_immutable_uuid_v7_public_ids(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            $user->public_id
        );

        $publicId = $user->public_id;
        $user->update(['name' => 'Updated account']);

        $this->assertSame($publicId, $user->fresh()->public_id);
    }

    public function test_new_tasks_receive_immutable_uuid_v7_public_ids(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();

        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            $todo->public_id
        );

        $publicId = $todo->public_id;
        $todo->update(['title' => 'Updated task']);

        $this->assertSame($publicId, $todo->fresh()->public_id);
    }

    public function test_public_ids_cannot_be_overridden_through_mass_assignment(): void
    {
        $user = User::factory()->create();
        $requestedPublicId = '01933f1e-7b2a-7c41-9a2e-4e3c8a1b5f10';

        $todo = new Todo([
            'public_id' => $requestedPublicId,
            'title' => 'Mass assignment protection',
            'status' => 0,
            'priority' => 1,
        ]);
        $todo->user()->associate($user);
        $todo->save();

        $this->assertNotSame($requestedPublicId, $todo->public_id);
        $this->assertNotSame((string) $user->getKey(), $user->public_id);
    }

    public function test_public_ids_are_unique_across_accounts_and_tasks(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $firstTodo = Todo::factory()->for($firstUser)->create();
        $secondTodo = Todo::factory()->for($secondUser)->create();

        $identifiers = [
            $firstUser->public_id,
            $secondUser->public_id,
            $firstTodo->public_id,
            $secondTodo->public_id,
        ];

        $this->assertCount(4, array_unique($identifiers));
    }
}
