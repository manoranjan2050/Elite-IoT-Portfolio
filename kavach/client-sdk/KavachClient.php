<?php

/**
 * KavachClient — drop-in license + update client for Kavach license server.
 *
 * Works in plain PHP apps (OpenPharma) and Laravel apps alike. No dependencies
 * beyond curl, zip and libsodium (all bundled with PHP 7.2+).
 *
 * Usage:
 *   require 'KavachClient.php';
 *   $kavach = new KavachClient([
 *       'server'      => 'https://kavach.example.com',
 *       'product'     => 'openpharma',
 *       'public_key'  => '<base64 public key from: php artisan kavach:keys>',
 *       'storage_dir' => __DIR__ . '/storage',   // must be writable, deny web access
 *       'app_version' => '1.0.0',
 *   ]);
 *
 *   $state = $kavach->check();
 *   if (! $state['licensed']) { show activation screen; }
 *   if ($kavach->isPro()) { enable pro features; }
 */
class KavachException extends \Exception
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}

class KavachClient
{
    /** Revalidate with the server once this many seconds have passed. */
    private const REVALIDATE_AFTER = 86400;      // 1 day

    /** Keep working without server contact for this long (offline grace). */
    private const OFFLINE_GRACE = 8 * 86400;     // 8 days

    private const LICENSE_FILE = 'kavach_license.json';

    private string $server;
    private string $product;
    private string $publicKey;
    private string $storageDir;
    private string $appVersion;
    private string $fingerprint;

    private ?array $payload = null;

    public function __construct(array $config)
    {
        foreach (['server', 'product', 'public_key', 'storage_dir', 'app_version'] as $required) {
            if (empty($config[$required])) {
                throw new \InvalidArgumentException("KavachClient config missing: {$required}");
            }
        }

        $this->server = rtrim($config['server'], '/');
        $this->product = $config['product'];
        $this->publicKey = $config['public_key'];
        $this->storageDir = rtrim($config['storage_dir'], '/\\');
        $this->appVersion = $config['app_version'];
        $this->fingerprint = $config['fingerprint'] ?? $this->defaultFingerprint();

        if (! is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0775, true);
        }
    }

    /* ---------------------------------------------------------------------
     * Licensing
     * ------------------------------------------------------------------- */

    /**
     * Activate this installation with a license key.
     * Returns the license payload. Throws KavachException on failure.
     */
    public function activate(string $licenseKey, ?string $label = null): array
    {
        $response = $this->post('/api/v1/activate', [
            'product' => $this->product,
            'license_key' => trim($licenseKey),
            'fingerprint' => $this->fingerprint,
            'label' => $label ?? ($_SERVER['HTTP_HOST'] ?? gethostname()),
            'app_version' => $this->appVersion,
        ]);

        $payload = $this->verifyAndDecode($response);
        $this->storeLicenseFile($response);
        $this->payload = $payload;

        return $payload;
    }

    /**
     * Main gate — call once per request (it is cheap: server is contacted at
     * most once per day, otherwise only the local signed file is read).
     *
     * Returns ['licensed' => bool, 'reason' => ?string, 'payload' => ?array].
     */
    public function check(): array
    {
        $stored = $this->loadLicenseFile();
        if (! $stored) {
            return ['licensed' => false, 'reason' => 'NO_LICENSE', 'payload' => null];
        }

        try {
            $payload = $this->verifyAndDecode($stored);
        } catch (KavachException) {
            $this->deleteLicenseFile(); // tampered or corrupted
            return ['licensed' => false, 'reason' => 'INVALID_FILE', 'payload' => null];
        }

        if ($payload['product'] !== $this->product || $payload['fingerprint'] !== $this->fingerprint) {
            return ['licensed' => false, 'reason' => 'WRONG_INSTALL', 'payload' => null];
        }

        $age = time() - ($payload['issued_at'] ?? 0);
        $locallyExpired = ! empty($payload['expires_at']) && strtotime($payload['expires_at']) < time();

        // Fresh enough and not expired: no network needed.
        if ($age < self::REVALIDATE_AFTER && ! $locallyExpired) {
            $this->payload = $payload;
            return ['licensed' => true, 'reason' => null, 'payload' => $payload];
        }

        // Time to phone home (daily heartbeat, or local expiry that a renewal may have lifted).
        try {
            $response = $this->post('/api/v1/validate', [
                'product' => $this->product,
                'license_key' => $payload['license_key'],
                'fingerprint' => $this->fingerprint,
                'app_version' => $this->appVersion,
            ]);
            $fresh = $this->verifyAndDecode($response);
            $this->storeLicenseFile($response);
            $this->payload = $fresh;

            return ['licensed' => true, 'reason' => null, 'payload' => $fresh];
        } catch (KavachException $e) {
            if ($e->errorCode === 'NETWORK') {
                // Server unreachable: honour the offline grace window.
                if (! $locallyExpired && $age < self::OFFLINE_GRACE) {
                    $this->payload = $payload;
                    return ['licensed' => true, 'reason' => 'OFFLINE_GRACE', 'payload' => $payload];
                }

                return ['licensed' => false, 'reason' => 'GRACE_EXPIRED', 'payload' => $payload];
            }

            // Authoritative rejection from the server (EXPIRED / SUSPENDED / ...).
            return ['licensed' => false, 'reason' => $e->errorCode, 'payload' => $payload];
        }
    }

    /** Free this installation's activation slot on the server and locally. */
    public function deactivate(): void
    {
        $payload = $this->payload ?? $this->tryDecodeStored();

        if ($payload) {
            try {
                $this->post('/api/v1/deactivate', [
                    'product' => $this->product,
                    'license_key' => $payload['license_key'],
                    'fingerprint' => $this->fingerprint,
                ]);
            } catch (KavachException) {
                // best effort — still remove the local file
            }
        }

        $this->deleteLicenseFile();
        $this->payload = null;
    }

    public function isValid(): bool
    {
        return $this->check()['licensed'];
    }

    public function payload(): ?array
    {
        return $this->payload;
    }

    public function tier(): string
    {
        return $this->payload['tier'] ?? 'normal';
    }

    public function isPro(): bool
    {
        return $this->tier() === 'pro';
    }

    public function isTrial(): bool
    {
        return (bool) ($this->payload['is_trial'] ?? false);
    }

    public function daysLeft(): ?int
    {
        return $this->payload['days_left'] ?? null;
    }

    /* ---------------------------------------------------------------------
     * Updates
     * ------------------------------------------------------------------- */

    /**
     * Ask the server whether a newer version exists.
     * Returns the signed update payload, e.g.:
     *   ['update_available' => true, 'latest_version' => '1.1.0',
     *    'changelog' => '...', 'sha256' => '...', 'download_url' => '...']
     */
    public function checkUpdate(string $channel = 'stable'): array
    {
        $payload = $this->payload ?? $this->tryDecodeStored();
        if (! $payload) {
            throw new KavachException('NO_LICENSE', 'Activate a license before checking updates.');
        }

        $response = $this->post('/api/v1/update/check', [
            'product' => $this->product,
            'license_key' => $payload['license_key'],
            'fingerprint' => $this->fingerprint,
            'current_version' => $this->appVersion,
            'channel' => $channel,
        ]);

        return $this->verifyAndDecode($response);
    }

    /**
     * Download the update zip to storage_dir and verify its sha256.
     * Returns the absolute path of the verified zip.
     */
    public function downloadUpdate(array $update): string
    {
        if (empty($update['download_url']) || empty($update['sha256'])) {
            throw new KavachException('BAD_UPDATE', 'Update payload has no download.');
        }

        $dest = $this->storageDir.'/kavach_update_'.$update['latest_version'].'.zip';

        $fh = fopen($dest, 'wb');
        $ch = curl_init($update['download_url']);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fh,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_FAILONERROR => true,
        ]);
        $ok = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fh);

        if (! $ok) {
            @unlink($dest);
            throw new KavachException('NETWORK', "Update download failed: {$err}");
        }

        if (! hash_equals($update['sha256'], hash_file('sha256', $dest))) {
            @unlink($dest);
            throw new KavachException('HASH_MISMATCH',
                'Downloaded file is corrupted or tampered. Try again.');
        }

        return $dest;
    }

    /**
     * Extract a verified update zip over the application directory.
     *
     * $protect — paths (relative to $appRoot) that must NEVER be overwritten:
     * user config, uploads, storage. Defaults cover common cases; pass your own.
     */
    public function installUpdate(string $zipPath, string $appRoot, array $protect = [
        '.env', 'config.php', 'storage', 'uploads',
    ]): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new KavachException('BAD_ZIP', 'Could not open update package.');
        }

        $appRoot = rtrim($appRoot, '/\\');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            $relative = str_replace('\\', '/', $entry);

            // zip-slip guard
            if (str_contains($relative, '..')) {
                continue;
            }

            foreach ($protect as $protected) {
                $protected = trim(str_replace('\\', '/', $protected), '/');
                if ($relative === $protected || str_starts_with($relative, $protected.'/')) {
                    continue 2;
                }
            }

            $target = $appRoot.'/'.$relative;

            if (str_ends_with($relative, '/')) {
                if (! is_dir($target)) {
                    mkdir($target, 0775, true);
                }
                continue;
            }

            $dir = dirname($target);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            copy("zip://{$zipPath}#{$entry}", $target);
        }

        $zip->close();
        @unlink($zipPath);
    }

    /* ---------------------------------------------------------------------
     * Internals
     * ------------------------------------------------------------------- */

    /** Stable per-installation identity: domain + docroot + host name. */
    private function defaultFingerprint(): string
    {
        $host = strtolower(preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'cli'));
        $docroot = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();

        return hash('sha256', implode('|', [$host, $docroot, php_uname('n')]));
    }

    private function post(string $path, array $body): array
    {
        $ch = curl_init($this->server.$path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);
        $raw = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new KavachException('NETWORK', "License server unreachable: {$err}");
        }

        $json = json_decode($raw, true);

        if (! is_array($json)) {
            throw new KavachException('NETWORK', "Unexpected response from license server (HTTP {$status}).");
        }

        if (empty($json['ok'])) {
            throw new KavachException(
                $json['code'] ?? 'SERVER_ERROR',
                $json['message'] ?? "License server error (HTTP {$status})."
            );
        }

        return $json;
    }

    /** Verify Ed25519 signature over the base64 payload string, then decode. */
    private function verifyAndDecode(array $response): array
    {
        $payloadB64 = $response['payload'] ?? '';
        $signature = base64_decode($response['signature'] ?? '', true);
        $publicKey = base64_decode($this->publicKey, true);

        if (! $payloadB64 || ! $signature || ! $publicKey
            || ! sodium_crypto_sign_verify_detached($signature, $payloadB64, $publicKey)) {
            throw new KavachException('BAD_SIGNATURE', 'License data failed verification.');
        }

        $payload = json_decode(base64_decode($payloadB64), true);

        if (! is_array($payload)) {
            throw new KavachException('BAD_SIGNATURE', 'License data unreadable.');
        }

        return $payload;
    }

    private function licenseFilePath(): string
    {
        return $this->storageDir.'/'.self::LICENSE_FILE;
    }

    private function storeLicenseFile(array $response): void
    {
        file_put_contents($this->licenseFilePath(), json_encode([
            'payload' => $response['payload'],
            'signature' => $response['signature'],
        ]), LOCK_EX);
    }

    private function loadLicenseFile(): ?array
    {
        $path = $this->licenseFilePath();
        if (! is_file($path)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($path), true);

        return is_array($json) ? $json : null;
    }

    private function deleteLicenseFile(): void
    {
        @unlink($this->licenseFilePath());
    }

    private function tryDecodeStored(): ?array
    {
        $stored = $this->loadLicenseFile();
        if (! $stored) {
            return null;
        }

        try {
            return $this->payload = $this->verifyAndDecode($stored);
        } catch (KavachException) {
            return null;
        }
    }
}
