<?php

namespace Tests\Unit;

use App\Services\HybridCryptoService;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class HybridCryptoServiceTest extends TestCase
{
    public function test_encrypt_and_decrypt_round_trip(): void
    {
        $this->useFixtureHybridKeys();

        $service = app(HybridCryptoService::class);
        $plaintext = 'serial=ABC123XYZ';

        $payload = $service->encryptSensitive($plaintext);

        $this->assertNotSame($plaintext, $payload);

        $decodedPayload = json_decode($payload, true);
        $this->assertIsArray($decodedPayload);
        $this->assertSame('hybrid_v1', $decodedPayload['version'] ?? null);
        $this->assertArrayHasKey('wrapped_key', $decodedPayload);

        $decryptedValue = $service->decryptSensitive($payload);

        $this->assertSame($plaintext, $decryptedValue);
    }

    public function test_decrypt_sensitive_supports_legacy_laravel_payload(): void
    {
        $this->useFixtureHybridKeys();

        $service = app(HybridCryptoService::class);
        $legacyPayload = Crypt::encryptString('legacy-sensitive-value');

        $decryptedValue = $service->decryptSensitive($legacyPayload);

        $this->assertSame('legacy-sensitive-value', $decryptedValue);
    }

    private function useFixtureHybridKeys(): void
    {
        config()->set('security.hybrid_crypto.private_key_path', base_path('tests/Fixtures/keys/hybrid_private.pem'));
        config()->set('security.hybrid_crypto.public_key_path', base_path('tests/Fixtures/keys/hybrid_public.pem'));
        config()->set('security.hybrid_crypto.private_key_passphrase', 'ESBHybrid2026');
    }
}
