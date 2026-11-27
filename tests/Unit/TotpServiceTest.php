<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\TotpService;
use Tests\TestCase;

class TotpServiceTest extends TestCase
{
    public function test_totp_matches_the_rfc_6238_sha1_test_vector(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

        $this->assertSame(
            1,
            app(TotpService::class)->matchingCounter($secret, '287082', 59)
        );
    }

    public function test_totp_rejects_malformed_codes(): void
    {
        $service = app(TotpService::class);

        $this->assertNull($service->matchingCounter('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', '12345', 59));
        $this->assertNull($service->matchingCounter('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'ABCDEF', 59));
        $this->assertNull($service->matchingCounter('', '287082', 59));
    }

    public function test_provisioning_uri_contains_the_standard_authenticator_parameters(): void
    {
        $user = new User();
        $user->setAttribute('email', 'person@example.com');

        config(['app.name' => 'Tymetry']);

        $uri = app(TotpService::class)->provisioningUri(
            $user,
            'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'
        );

        $this->assertStringStartsWith('otpauth://totp/', $uri);
        $this->assertStringContainsString('secret=GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $uri);
        $this->assertStringContainsString('issuer=Tymetry', $uri);
        $this->assertStringContainsString('algorithm=SHA1', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }
}
