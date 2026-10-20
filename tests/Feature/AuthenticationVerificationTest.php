<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AuthenticationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_email_requires_a_verification_code_before_login(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => 'correct-password',
            'two_factor_enabled' => false,
        ]);

        $otp = Mockery::mock(OtpService::class);
        $otp->shouldReceive('send')->once()->with(Mockery::type(User::class), 'email_verification');
        $this->app->instance(OtpService::class, $otp);

        $this->post(route('auth.login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('otp.index'))
            ->assertSessionHas('pending_auth_user_id', $user->id)
            ->assertSessionHas('pending_auth_purpose', 'email_verification');

        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_successful_email_verification_marks_email_verified_and_logs_in(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'two_factor_enabled' => false,
        ]);

        $otp = Mockery::mock(OtpService::class);
        $otp->shouldReceive('verify')->once()->with(Mockery::type(User::class), 'email_verification', '123456')->andReturnTrue();
        $this->app->instance(OtpService::class, $otp);

        $this->withSession([
            'pending_auth_user_id' => $user->id,
            'pending_auth_purpose' => 'email_verification',
            'pending_auth_remember' => false,
        ])->post(route('otp.verify'), ['code' => '123456'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_development_bypass_does_not_bypass_enabled_two_factor_authentication(): void
    {
        config(['authentication.login_verification_bypass' => true]);

        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => 'correct-password',
            'two_factor_enabled' => true,
        ]);

        $otp = Mockery::mock(OtpService::class);
        $otp->shouldReceive('send')->once()->with(Mockery::type(User::class), 'two_factor');
        $this->app->instance(OtpService::class, $otp);

        $this->post(route('auth.login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('otp.index'))
            ->assertSessionHas('pending_auth_purpose', 'two_factor');

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_development_bypass_skips_only_email_verification(): void
    {
        config(['authentication.login_verification_bypass' => true]);

        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => 'correct-password',
            'two_factor_enabled' => false,
        ]);

        $this->post(route('auth.login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
    public function test_enabled_two_factor_requires_a_code_after_password_login(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'correct-password',
            'two_factor_enabled' => true,
        ]);

        $otp = Mockery::mock(OtpService::class);
        $otp->shouldReceive('send')->once()->with(Mockery::type(User::class), 'two_factor');
        $this->app->instance(OtpService::class, $otp);

        $this->post(route('auth.login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('otp.index'))
            ->assertSessionHas('pending_auth_purpose', 'two_factor');

        $this->assertGuest();
    }

    public function test_successful_two_factor_code_finishes_login(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);

        $otp = Mockery::mock(OtpService::class);
        $otp->shouldReceive('verify')->once()->with(Mockery::type(User::class), 'two_factor', '654321')->andReturnTrue();
        $this->app->instance(OtpService::class, $otp);

        $this->withSession([
            'pending_auth_user_id' => $user->id,
            'pending_auth_purpose' => 'two_factor',
            'pending_auth_remember' => false,
        ])->post(route('otp.verify'), ['code' => '654321'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

}
