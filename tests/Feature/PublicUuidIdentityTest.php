<?php

namespace Tests\Feature;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
        $user->forceFill(['public_id' => (string) Str::uuid7()])->save();
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
        $todo->forceFill(['public_id' => (string) Str::uuid7()])->save();
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
        $todo->setAttribute('public_id', $requestedPublicId);
        $todo->save();

        $requestedUserPublicId = (string) Str::uuid7();
        $newUser = User::factory()->make();
        $newUser->setAttribute('public_id', $requestedUserPublicId);
        $newUser->save();

        $this->assertNotSame($requestedPublicId, $todo->public_id);
        $this->assertNotSame($requestedUserPublicId, $newUser->public_id);
        $this->assertNotSame((string) $user->getKey(), $user->public_id);
    }

    public function test_migration_backfills_uuid_v7_for_existing_accounts_and_tasks(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->for($user)->create();

        $userId = $user->getKey();
        $todoId = $todo->getKey();

        Schema::table('todos', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        $migration = require database_path('migrations/2026_11_19_120000_add_public_ids_to_users_and_todos.php');
        $migration->up();

        $userPublicId = DB::table('users')->where('id', $userId)->value('public_id');
        $todoPublicId = DB::table('todos')->where('id', $todoId)->value('public_id');

        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            $userPublicId
        );
        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            $todoPublicId
        );
        $this->assertCount(2, array_unique([$userPublicId, $todoPublicId]));
    }

    public function test_uuid_identity_changes_preserve_hidden_encrypted_mfa_attributes(): void
    {
        $user = new User();
        $user->forceFill([
            'two_factor_secret' => 'BASE32-SECRET',
            'two_factor_recovery_codes' => ['hashed-recovery-code'],
        ]);

        $serialized = $user->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $serialized);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $serialized);
        $this->assertSame('BASE32-SECRET', $user->two_factor_secret);
        $this->assertSame(['hashed-recovery-code'], $user->two_factor_recovery_codes);
        $this->assertSame('encrypted', $user->getCasts()['two_factor_secret']);
        $this->assertSame('encrypted:array', $user->getCasts()['two_factor_recovery_codes']);
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
