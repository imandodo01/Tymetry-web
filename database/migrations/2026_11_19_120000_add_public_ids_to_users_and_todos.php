<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->unique();
        });

        Schema::table('todos', function (Blueprint $table): void {
            $table->uuid('public_id')->nullable()->unique();
        });

        DB::table('users')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->whereNull('public_id')
                        ->update(['public_id' => (string) Str::uuid7()]);
                }
            });

        DB::table('todos')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(100, function ($todos): void {
                foreach ($todos as $todo) {
                    DB::table('todos')
                        ->where('id', $todo->id)
                        ->whereNull('public_id')
                        ->update(['public_id' => (string) Str::uuid7()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
