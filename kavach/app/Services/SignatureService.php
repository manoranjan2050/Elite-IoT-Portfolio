<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Ed25519 signing of API payloads (libsodium).
 *
 * The private key lives ONLY on this server (storage/app/keys/).
 * Each client app ships the public key and verifies every payload,
 * so a cracked/hosts-file-redirected server cannot mint valid licenses.
 */
class SignatureService
{
    private const SECRET_PATH = 'keys/signing.key';
    private const PUBLIC_PATH = 'keys/signing.pub';

    public function generateKeypair(bool $force = false): array
    {
        if (! $force && Storage::exists(self::SECRET_PATH)) {
            throw new RuntimeException(
                'Keypair already exists. Regenerating it will invalidate every '
                .'license file cached by installed apps. Use --force if you are sure.'
            );
        }

        $pair = sodium_crypto_sign_keypair();
        $secret = base64_encode(sodium_crypto_sign_secretkey($pair));
        $public = base64_encode(sodium_crypto_sign_publickey($pair));

        Storage::put(self::SECRET_PATH, $secret);
        Storage::put(self::PUBLIC_PATH, $public);

        return ['public' => $public];
    }

    public function publicKey(): string
    {
        return trim(Storage::get(self::PUBLIC_PATH));
    }

    /**
     * Returns ['payload' => base64(json), 'signature' => base64(ed25519 sig)].
     * The client verifies the signature over the raw base64 payload string,
     * then json-decodes it — no JSON canonicalization headaches.
     */
    public function sign(array $data): array
    {
        if (! Storage::exists(self::SECRET_PATH)) {
            throw new RuntimeException('Signing key missing. Run: php artisan kavach:keys');
        }

        $secret = base64_decode(trim(Storage::get(self::SECRET_PATH)));
        $payload = base64_encode(json_encode($data, JSON_UNESCAPED_SLASHES));
        $signature = sodium_crypto_sign_detached($payload, $secret);

        return [
            'payload' => $payload,
            'signature' => base64_encode($signature),
        ];
    }
}
