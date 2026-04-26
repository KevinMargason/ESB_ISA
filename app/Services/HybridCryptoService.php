<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;

class HybridCryptoService
{
    private const VERSION = 'hybrid_v1';
    private const AES_CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;

    public function encryptSensitive(string $plaintext): string
    {
        $publicKey = $this->loadPublicKey();

        $dek = random_bytes(32);
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::AES_CIPHER,
            $dek,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false || $tag === '') {
            throw new RuntimeException('Unable to encrypt sensitive payload with AES-GCM.');
        }

        $wrappedKey = '';
        $wrapped = openssl_public_encrypt($dek, $wrappedKey, $publicKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $wrapped) {
            throw new RuntimeException('Unable to wrap DEK using RSA public key: '.$this->opensslErrorMessage());
        }

        $encodedPayload = json_encode([
            'version' => self::VERSION,
            'cipher' => self::AES_CIPHER,
            'ciphertext' => base64_encode($ciphertext),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'wrapped_key' => base64_encode($wrappedKey),
        ], JSON_UNESCAPED_SLASHES);

        if (! is_string($encodedPayload)) {
            throw new RuntimeException('Unable to encode hybrid encrypted payload.');
        }

        return $encodedPayload;
    }

    public function decryptSensitive(string $payload): string
    {
        $decoded = $this->decodeHybridPayload($payload);

        // Backward compatibility for legacy Laravel Crypt::encryptString payloads.
        if ($decoded === null) {
            return Crypt::decryptString($payload);
        }

        $privateKey = $this->loadPrivateKey();

        $wrappedKey = $this->base64Decode($decoded['wrapped_key'], 'wrapped_key');
        $iv = $this->base64Decode($decoded['iv'], 'iv');
        $tag = $this->base64Decode($decoded['tag'], 'tag');
        $ciphertext = $this->base64Decode($decoded['ciphertext'], 'ciphertext');

        $dek = '';
        $unwrapped = openssl_private_decrypt($wrappedKey, $dek, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
        if (! $unwrapped) {
            throw new RuntimeException('Unable to unwrap DEK using RSA private key: '.$this->opensslErrorMessage());
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::AES_CIPHER,
            $dek,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Unable to decrypt sensitive payload with AES-GCM.');
        }

        return $plaintext;
    }

    /**
     * @return array<string, string>|null
     */
    private function decodeHybridPayload(string $payload): ?array
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return null;
        }

        if (($decoded['version'] ?? null) !== self::VERSION) {
            return null;
        }

        foreach (['ciphertext', 'iv', 'tag', 'wrapped_key'] as $requiredField) {
            if (! isset($decoded[$requiredField]) || ! is_string($decoded[$requiredField]) || $decoded[$requiredField] === '') {
                throw new RuntimeException('Invalid hybrid payload: missing '.$requiredField.'.');
            }
        }

        return $decoded;
    }

    private function base64Decode(string $encodedValue, string $field): string
    {
        $decoded = base64_decode($encodedValue, true);

        if ($decoded === false || $decoded === '') {
            throw new RuntimeException('Invalid base64 value for '.$field.'.');
        }

        return $decoded;
    }

    private function loadPrivateKey()
    {
        $configuredPath = (string) config('security.hybrid_crypto.private_key_path');

        return $this->loadKey($configuredPath, 'private');
    }

    private function loadPublicKey()
    {
        $configuredPath = (string) config('security.hybrid_crypto.public_key_path');

        return $this->loadKey($configuredPath, 'public');
    }

    private function loadKey(string $configuredPath, string $type)
    {
        if ($configuredPath === '') {
            throw new RuntimeException('Hybrid crypto '.$type.' key path is not configured.');
        }

        $resolvedPath = $this->resolvePath($configuredPath);
        if (! is_file($resolvedPath)) {
            throw new RuntimeException('Hybrid crypto '.$type.' key file not found at: '.$resolvedPath);
        }

        $keyContents = file_get_contents($resolvedPath);
        if (! is_string($keyContents) || trim($keyContents) === '') {
            throw new RuntimeException('Hybrid crypto '.$type.' key file is empty: '.$resolvedPath);
        }

        $key = $type === 'public'
            ? openssl_pkey_get_public($keyContents)
            : openssl_pkey_get_private(
                $keyContents,
                (string) config('security.hybrid_crypto.private_key_passphrase', '')
            );

        if ($key === false) {
            throw new RuntimeException('Unable to load '.$type.' key from '.$resolvedPath.': '.$this->opensslErrorMessage());
        }

        return $key;
    }

    private function resolvePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        if (str_starts_with($path, '/')) {
            return true;
        }

        return preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function opensslErrorMessage(): string
    {
        $error = openssl_error_string();

        if (! is_string($error) || $error === '') {
            return 'unknown OpenSSL error';
        }

        return $error;
    }
}
