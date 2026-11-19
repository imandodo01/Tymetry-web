<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?string $sentCode = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_sent_code_can_be_verified_once(): void
    {
        $user = $this->makeUser();
        $this->expectOtpEmail();

        $service = app(OtpService::class);
        $service->send($user, 'email_verification');

        $this->assertNotNull($this->sentCode);
        $this->assertTrue($service->verify($user, 'email_verification', $this->sentCode));
        $this->assertFalse($service->verify($user, 'email_verification', $this->sentCode));
    }

    public function test_email_verification_message_uses_the_account_address_and_expected_subject(): void
    {
        $user = $this->makeUser();
        $this->expectOtpEmailFor($user, 'Verify your Tymetry email');

        app(OtpService::class)->send($user, 'email_verification');

        $this->assertNotNull($this->sentCode);
    }

    public function test_legacy_second_factor_message_uses_the_sign_in_subject(): void
    {
        $user = $this->makeUser();
        $this->expectOtpEmailFor($user, 'Your Tymetry sign-in code');

        app(OtpService::class)->send($user, 'two_factor');

        $this->assertNotNull($this->sentCode);
    }

    public function test_invalid_attempts_exhaust_and_remove_the_challenge(): void
    {
        $user = $this->makeUser();
        $this->expectOtpEmail();

        $service = app(OtpService::class);
        $service->send($user, 'email_verification');

        for ($attempt = 0; $attempt < config('authentication.otp_max_attempts', 5); $attempt++) {
            $this->assertFalse($service->verify($user, 'email_verification', '000000'));
        }

        $this->assertNotNull($this->sentCode);
        $this->assertFalse($service->verify($user, 'email_verification', $this->sentCode));
    }

    public function test_resend_is_rejected_during_the_cooldown_window(): void
    {
        $user = $this->makeUser();
        $this->expectOtpEmail();

        $service = app(OtpService::class);
        $service->send($user, 'email_verification');

        $thrown = false;

        try {
            $service->send($user, 'email_verification');
        } catch (RuntimeException $exception) {
            $thrown = true;
            $this->assertSame('Please wait before requesting another code.', $exception->getMessage());
        }

        $this->assertTrue($thrown, 'A resend inside the cooldown window must be rejected.');
    }

    public function test_expired_challenge_cannot_be_verified(): void
    {
        $user = $this->makeUser();
        $this->expectOtpEmail();

        $service = app(OtpService::class);
        $service->send($user, 'email_verification');

        $this->travel(config('authentication.otp_expiry_minutes', 5) + 1)->minutes();

        $this->assertNotNull($this->sentCode);
        $this->assertFalse($service->verify($user, 'email_verification', $this->sentCode));
    }

    public function test_failed_email_delivery_removes_the_prepared_challenge(): void
    {
        $user = $this->makeUser();
        $challengeKey = 'auth:otp:email_verification:123:otp-test@example.com';

        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new RuntimeException('Mail transport unavailable.'));

        try {
            app(OtpService::class)->send($user, 'email_verification');
            $this->fail('The mail transport exception should be propagated.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Mail transport unavailable.', $exception->getMessage());
        }

        $this->assertFalse(Cache::has($challengeKey));
    }

    public function test_send_does_not_overwrite_a_challenge_while_its_lock_is_held(): void
    {
        $user = $this->makeUser();
        $lock = Cache::lock($this->lockKey($user, 'email_verification'), 30);

        $this->assertTrue($lock->get());

        $thrown = false;

        try {
            app(OtpService::class)->send($user, 'email_verification');
        } catch (RuntimeException $exception) {
            $thrown = true;
            $this->assertStringContainsString('already being processed', $exception->getMessage());
        } finally {
            $lock->release();
        }

        $this->assertTrue($thrown, 'A concurrent send must not enter the protected challenge operation.');
    }

    public function test_database_cache_store_supports_the_otp_lock_workflow(): void
    {
        config(['cache.default' => 'database']);
        Cache::store('database')->flush();

        $user = $this->makeUser();
        $this->expectOtpEmail();

        $service = app(OtpService::class);
        $service->send($user, 'email_verification');

        $this->assertNotNull($this->sentCode);
        $this->assertTrue($service->verify($user, 'email_verification', $this->sentCode));
    }

    public function test_unsupported_otp_purpose_is_rejected(): void
    {
        $thrown = false;

        try {
            app(OtpService::class)->verify($this->makeUser(), 'password_reset', '123456');
        } catch (RuntimeException $exception) {
            $thrown = true;
            $this->assertSame('Unsupported OTP purpose.', $exception->getMessage());
        }

        $this->assertTrue($thrown);
    }

    private function makeUser(): User
    {
        $user = new User();
        $user->setAttribute('id', 123);
        $user->setAttribute('email', 'otp-test@example.com');

        return $user;
    }

    private function expectOtpEmail(): void
    {
        $this->sentCode = null;

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function ($message, $callback): bool {
                if (preg_match('/code is (\d{6})/', $message, $matches) === 1) {
                    $this->sentCode = $matches[1];
                }

                return true;
            });
    }

    private function expectOtpEmailFor(User $user, string $subject): void
    {
        $this->sentCode = null;

        $mailMessage = Mockery::mock(Message::class);
        $mailMessage->shouldReceive('to')->once()->with($user->email)->andReturnSelf();
        $mailMessage->shouldReceive('subject')->once()->with($subject)->andReturnSelf();

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function ($message, $callback) use ($mailMessage): bool {
                $this->assertStringContainsString('expires in', $message);

                if (preg_match('/code is (\\d{6})/', $message, $matches) === 1) {
                    $this->sentCode = $matches[1];
                }

                $callback($mailMessage);

                return true;
            });
    }

    private function lockKey(User $user, string $purpose): string
    {
        return 'auth:otp:' . $purpose . ':' . $user->getKey() . ':' . strtolower($user->email) . ':lock';
    }
}
