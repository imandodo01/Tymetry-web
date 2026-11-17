<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_link_requests_are_rate_limited(): void
    {
        $user = User::factory()->create();
        Mail::fake();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.resetLink'), [
                'email' => $user->email,
            ])->assertRedirect();
        }

        $this->post(route('password.resetLink'), [
            'email' => $user->email,
        ])->assertTooManyRequests();
    }

    public function test_password_reset_submissions_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.update'), [
                'token' => 'invalid-token',
                'email' => 'user@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertRedirect();
        }

        $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertTooManyRequests();
    }
}
