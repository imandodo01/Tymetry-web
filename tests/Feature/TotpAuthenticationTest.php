<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\TotpService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class TotpAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_begin_setup_stores_an_encrypted_pending_secret_without_enabling_mfa(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'correct-password',
        ]);

        $response = $this->actingAs($user)->post(route('profile.two-factor.setup'), [
            'current_password' => 'correct-password',
        ]);

        $response->assertRedirect();
        $freshUser = $user->fresh();
        $storedSecret = DB::table('users')->where('id', $user->id)->value('two_factor_secret');

        $this->assertNotEmpty($freshUser->two_factor_secret);
        $this->assertNotSame($freshUser->two_factor_secret, $storedSecret);
        $this->assertFalse($freshUser->two_factor_enabled);
        $this->assertNull($freshUser->two_factor_confirmed_at);
        $this->assertEquals(now()->addMinutes(10)->timestamp, $freshUser->two_factor_setup_expires_at->timestamp);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'mfa_setup_started',
        ]);
    }

    public function test_totp_secret_and_recovery_hashes_are_hidden_from_user_serialization(): void
    {
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_recovery_codes' => ['hashed-recovery-code'],
        ])->save();

        $freshUser = $user->fresh();
        $serialized = $freshUser->toArray();

        $this->assertArrayNotHasKey('two_factor_secret', $serialized);
        $this->assertArrayNotHasKey('two_factor_recovery_codes', $serialized);
        $this->assertSame(self::RFC_SECRET, $freshUser->two_factor_secret);
        $this->assertSame(['hashed-recovery-code'], $freshUser->two_factor_recovery_codes);
    }

    public function test_profile_renders_local_qr_setup_and_manual_key_fallback(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->forceFill([
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_setup_expires_at' => now()->addMinutes(10),
        ])->save();

        $response = $this->actingAs($user)->get(route('profile.index'));

        $response->assertOk()
            ->assertSee('totp-qr-code', false)
            ->assertSee('assets/vendor/qrcode/qrcode.min.js')
            ->assertSee('Manual setup key')
            ->assertSee('otpauth://totp/', false);

        $this->assertFileExists(public_path('assets/vendor/qrcode/qrcode.min.js'));
        $this->assertFileExists(public_path('assets/vendor/qrcode/LICENSE'));
    }

    public function test_unverified_email_cannot_start_authenticator_enrollment(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
            'password' => 'correct-password',
        ]);

        $this->actingAs($user)
            ->post(route('profile.two-factor.setup'), [
                'current_password' => 'correct-password',
            ])
            ->assertSessionHasErrors('two_factor_enabled');

        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_pending_setup_secret_is_cleared_when_the_ten_minute_expiry_is_reached(): void
    {
        $this->travelTo(Carbon::parse('2026-11-19 10:00:00', 'UTC'));

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_setup_expires_at' => now()->addMinutes(10),
        ])->save();

        $this->travel(10)->minutes();

        $this->assertNull(app(TotpService::class)->pendingSetupSecret($user));

        $freshUser = $user->fresh();
        $this->assertNull($freshUser->two_factor_secret);
        $this->assertNull($freshUser->two_factor_setup_expires_at);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'mfa_setup_expired',
        ]);
    }

    public function test_confirmation_rejects_and_clears_an_expired_pending_setup(): void
    {
        $this->travelTo(Carbon::createFromTimestamp(59, 'UTC'));

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_setup_expires_at' => now()->subSecond(),
        ])->save();

        $this->assertNull(app(TotpService::class)->confirmSetup($user, '287082'));

        $freshUser = $user->fresh();
        $this->assertNull($freshUser->two_factor_secret);
        $this->assertNull($freshUser->two_factor_setup_expires_at);
        $this->assertFalse($freshUser->two_factor_enabled);
    }

    public function test_confirming_a_valid_authenticator_code_enables_totp_and_returns_recovery_codes(): void
    {
        $this->travelTo(Carbon::createFromTimestamp(59, 'UTC'));

        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_setup_expires_at' => now()->addMinutes(10),
        ])->save();

        $recoveryCodes = app(TotpService::class)->confirmSetup($user, '287082');
        $freshUser = $user->fresh();

        $this->assertCount(8, $recoveryCodes);
        $this->assertTrue($freshUser->two_factor_enabled);
        $this->assertSame('totp', $freshUser->two_factor_method);
        $this->assertNotNull($freshUser->two_factor_confirmed_at);
        $this->assertNull($freshUser->two_factor_setup_expires_at);
        $this->assertSame(1, $freshUser->two_factor_last_used_counter);
        $this->assertCount(8, $freshUser->two_factor_recovery_codes);
        $this->assertNotSame($recoveryCodes[0], $freshUser->two_factor_recovery_codes[0]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'mfa_enabled',
        ]);
    }

    public function test_totp_login_accepts_a_current_code_only_once(): void
    {
        $this->travelTo(Carbon::createFromTimestamp(59, 'UTC'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'correct-password',
            'two_factor_enabled' => true,
        ]);
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $attemptKey = 'totp-login:' . $user->getKey();
        RateLimiter::hit($attemptKey, 60);
        RateLimiter::hit($attemptKey, 60);

        $otp = Mockery::mock(OtpService::class);
        $otp->shouldNotReceive('send');
        $this->app->instance(OtpService::class, $otp);

        $this->post(route('auth.login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect(route('otp.index'))
            ->assertSessionHas('pending_auth_purpose', 'totp');

        $this->assertGuest();

        $this->post(route('otp.verify'), ['code' => '287082'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $user->fresh()->two_factor_last_used_counter);
        $this->assertSame(0, RateLimiter::attempts($attemptKey));
        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'mfa_totp_verified',
        ]);

        Auth::logout();
        $this->assertGuest();

        $this->withSession([
            'pending_auth_user_id' => $user->id,
            'pending_auth_purpose' => 'totp',
            'pending_auth_remember' => false,
        ])->post(route('otp.verify'), ['code' => '287082'])
            ->assertRedirect(route('otp.index'))
            ->assertSessionHasErrors('code');

        $this->assertGuest();
        $this->assertSame(1, $user->fresh()->two_factor_last_used_counter);
        $this->assertSame(1, RateLimiter::attempts($attemptKey));
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_totp_verification_input_supports_alphanumeric_recovery_codes(): void
    {
        $response = $this->withSession([
            'pending_auth_user_id' => 1,
            'pending_auth_purpose' => 'totp',
        ])->get(route('otp.index'));

        $response->assertOk()
            ->assertSee('inputmode="text"', false)
            ->assertSee('autocapitalize="characters"', false)
            ->assertSee('pattern="([0-9]{6}|[A-Za-z0-9]{10})"', false);
    }

    public function test_totp_login_attempts_are_limited_per_account_across_ip_addresses(): void
    {
        $this->travelTo(Carbon::createFromTimestamp(59, 'UTC'));

        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $attemptKey = 'totp-login:' . $user->getKey();
        RateLimiter::clear($attemptKey);
        $pendingSession = [
            'pending_auth_user_id' => $user->getKey(),
            'pending_auth_purpose' => 'totp',
        ];

        foreach (range(1, 5) as $ipSuffix) {
            $this->withSession($pendingSession)
                ->withServerVariables(['REMOTE_ADDR' => "198.51.100.{$ipSuffix}"])
                ->post(route('otp.verify'), ['code' => '000000'])
                ->assertSessionHasErrors('code');
        }

        $this->withSession($pendingSession)
            ->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->post(route('otp.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code')
            ->assertSessionHasErrors([
                'code' => 'Too many invalid authenticator attempts. Please wait before trying again.',
            ]);

        $this->assertSame(5, RateLimiter::attempts($attemptKey));
        $this->assertGuest();
    }

    public function test_totp_verification_fails_closed_when_account_lock_is_contended(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'two_factor_enabled' => true,
        ]);
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $attemptKey = 'totp-login:' . $user->getKey();
        RateLimiter::clear($attemptKey);

        $lock = Cache::lock('totp-login-verification:' . $user->getKey(), 30);
        $this->assertTrue($lock->get());

        try {
            $this->withSession([
                'pending_auth_user_id' => $user->getKey(),
                'pending_auth_purpose' => 'totp',
            ])->post(route('otp.verify'), ['code' => '000000'])
                ->assertSessionHasErrors([
                    'code' => 'Another authenticator verification is in progress. Please wait a moment and try again.',
                ]);

            $this->assertSame(0, RateLimiter::attempts($attemptKey));
            $this->assertGuest();
        } finally {
            $lock->release();
        }
    }

    public function test_incomplete_totp_enrollment_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->assertFalse(app(TotpService::class)->verifyLogin($user, '287082'));
    }

    public function test_recovery_code_is_consumed_after_one_successful_use(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('ABCDE12345')],
        ])->save();

        $service = app(TotpService::class);

        $this->assertTrue($service->verifyLogin($user, 'abcde12345'));
        $this->assertFalse($service->verifyLogin($user, 'ABCDE12345'));
        $this->assertSame([], $user->fresh()->two_factor_recovery_codes);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'mfa_recovery_code_used',
        ]);
    }

    public function test_disabling_totp_clears_secrets_and_records_the_security_event(): void
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);
        $user->forceFill([
            'two_factor_method' => 'totp',
            'two_factor_secret' => self::RFC_SECRET,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [Hash::make('ABCDE12345')],
            'two_factor_last_used_counter' => 1,
        ])->save();

        app(TotpService::class)->disable($user);

        $freshUser = $user->fresh();
        $this->assertFalse($freshUser->two_factor_enabled);
        $this->assertNull($freshUser->two_factor_method);
        $this->assertNull($freshUser->two_factor_secret);
        $this->assertNull($freshUser->two_factor_recovery_codes);
        $this->assertNull($freshUser->two_factor_confirmed_at);
        $this->assertNull($freshUser->two_factor_setup_expires_at);
        $this->assertNull($freshUser->two_factor_last_used_counter);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'mfa_disabled',
        ]);
    }

    public function test_legacy_email_mfa_remains_active_until_the_user_migrates_to_totp(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'correct-password',
            'two_factor_enabled' => true,
        ]);
        $user->forceFill(['two_factor_method' => 'email'])->save();

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
}
