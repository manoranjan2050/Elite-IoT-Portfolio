<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Product;
use App\Models\Release;
use App\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UpdateApiController extends Controller
{
    public function __construct(private SignatureService $signatures)
    {
    }

    /**
     * A valid, activated license asks: "is there something newer than my version?"
     * Answer is signed, and includes a 30-minute signed download URL + sha256.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product' => 'required|string',
            'license_key' => 'required|string',
            'fingerprint' => 'required|string|max:191',
            'current_version' => 'required|string|max:20',
            'channel' => 'nullable|in:stable,beta',
        ]);

        $product = Product::where('slug', $data['product'])->where('is_active', true)->first();
        $license = $product?->licenses()->where('license_key', strtoupper(trim($data['license_key'])))->first();

        if (! $license) {
            return $this->fail('INVALID_KEY', 'License key not found for this product.', 404);
        }

        if ($license->status === 'suspended' || $license->syncStatus() !== 'active') {
            return $this->fail('LICENSE_INACTIVE', 'Updates require an active license.', 403);
        }

        if (! $license->activations()->where('fingerprint', $data['fingerprint'])->exists()) {
            return $this->fail('NOT_ACTIVATED', 'This installation is not activated.', 404);
        }

        $latest = $this->latestRelease($product, $data['channel'] ?? 'stable', $license->tier);

        $payload = [
            'product' => $product->slug,
            'current_version' => $data['current_version'],
            'issued_at' => now()->unix(),
        ];

        if (! $latest || version_compare($latest->version, $data['current_version'], '<=')) {
            $payload += [
                'update_available' => false,
                'latest_version' => $latest?->version ?? $data['current_version'],
            ];
        } else {
            $payload += [
                'update_available' => true,
                'latest_version' => $latest->version,
                'changelog' => $latest->changelog,
                'file_size' => $latest->file_size,
                'sha256' => $latest->file_hash,
                'released_at' => $latest->released_at?->toIso8601String(),
                'download_url' => URL::temporarySignedRoute(
                    'api.update.download',
                    now()->addMinutes(30),
                    [
                        'release' => $latest->id,
                        'license' => $license->id,
                        'from' => $data['current_version'],
                    ],
                ),
            ];
        }

        return response()->json([
            'ok' => true,
            ...$this->signatures->sign($payload),
        ]);
    }

    /** Streams the zip. Only reachable via the temporary signed URL above. */
    public function download(Request $request, Release $release, License $license): StreamedResponse|JsonResponse
    {
        if (! $release->is_active || $license->syncStatus() !== 'active') {
            return $this->fail('GONE', 'This download is no longer available.', 410);
        }

        if (! Storage::exists($release->file_path)) {
            return $this->fail('FILE_MISSING', 'Release file missing on server.', 500);
        }

        $release->downloads()->create([
            'license_id' => $license->id,
            'ip' => $request->ip(),
            'from_version' => $request->query('from'),
        ]);

        $name = "{$release->product->slug}-{$release->version}.zip";

        return Storage::download($release->file_path, $name);
    }

    private function latestRelease(Product $product, string $channel, string $tier): ?Release
    {
        return $product->releases()
            ->active()
            ->where('channel', $channel)
            ->get()
            ->filter(fn (Release $r) => $r->availableForTier($tier))
            ->sort(fn (Release $a, Release $b) => version_compare($b->version, $a->version))
            ->first();
    }

    private function fail(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'code' => $code,
            'message' => $message,
        ], $status);
    }
}
