<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activation;
use App\Models\License;
use App\Models\Product;
use App\Services\SignatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseApiController extends Controller
{
    public function __construct(private SignatureService $signatures)
    {
    }

    public function activate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product' => 'required|string',
            'license_key' => 'required|string',
            'fingerprint' => 'required|string|max:191',
            'label' => 'nullable|string|max:191',
            'app_version' => 'nullable|string|max:20',
        ]);

        [$license, $error] = $this->resolveLicense($data['product'], $data['license_key']);
        if ($error) {
            return $error;
        }

        $activation = $license->activations()
            ->where('fingerprint', $data['fingerprint'])
            ->first();

        if (! $activation) {
            if ($license->activations()->count() >= $license->max_activations) {
                return $this->fail('MAX_ACTIVATIONS',
                    'This key is already used on the maximum number of installations. '
                    .'Deactivate another installation first.', 403);
            }

            $activation = $license->activations()->create([
                'fingerprint' => $data['fingerprint'],
                'label' => $data['label'] ?? null,
            ]);
        }

        $this->touchActivation($activation, $request, $data['app_version'] ?? null);

        return $this->signedLicense($license, $activation);
    }

    public function validateLicense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product' => 'required|string',
            'license_key' => 'required|string',
            'fingerprint' => 'required|string|max:191',
            'app_version' => 'nullable|string|max:20',
        ]);

        [$license, $error] = $this->resolveLicense($data['product'], $data['license_key']);
        if ($error) {
            return $error;
        }

        $activation = $license->activations()
            ->where('fingerprint', $data['fingerprint'])
            ->first();

        if (! $activation) {
            return $this->fail('NOT_ACTIVATED',
                'This installation is not activated. Activate the license first.', 404);
        }

        $this->touchActivation($activation, $request, $data['app_version'] ?? null);

        return $this->signedLicense($license, $activation);
    }

    public function deactivate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product' => 'required|string',
            'license_key' => 'required|string',
            'fingerprint' => 'required|string|max:191',
        ]);

        $product = Product::where('slug', $data['product'])->first();
        $license = $product?->licenses()->where('license_key', $data['license_key'])->first();

        if (! $license) {
            return $this->fail('INVALID_KEY', 'License key not found for this product.', 404);
        }

        $deleted = $license->activations()
            ->where('fingerprint', $data['fingerprint'])
            ->delete();

        return response()->json([
            'ok' => true,
            'deactivated' => $deleted > 0,
        ]);
    }

    /** @return array{0: ?License, 1: ?JsonResponse} */
    private function resolveLicense(string $productSlug, string $key): array
    {
        $product = Product::where('slug', $productSlug)->where('is_active', true)->first();

        if (! $product) {
            return [null, $this->fail('UNKNOWN_PRODUCT', 'Unknown product.', 404)];
        }

        $license = $product->licenses()->where('license_key', strtoupper(trim($key)))->first();

        if (! $license) {
            return [null, $this->fail('INVALID_KEY', 'License key not found for this product.', 404)];
        }

        if ($license->status === 'suspended') {
            return [null, $this->fail('SUSPENDED',
                'This license is suspended. Contact support.', 403)];
        }

        if ($license->syncStatus() === 'expired') {
            return [null, $this->fail('EXPIRED',
                'This license has expired. Please renew your plan.', 403)];
        }

        return [$license, null];
    }

    private function touchActivation(Activation $activation, Request $request, ?string $appVersion): void
    {
        $activation->update([
            'ip' => $request->ip(),
            'app_version' => $appVersion ?? $activation->app_version,
            'last_check_at' => now(),
        ]);
    }

    private function signedLicense(License $license, Activation $activation): JsonResponse
    {
        $license->loadMissing(['plan', 'product', 'customer']);

        $payload = [
            'product' => $license->product->slug,
            'license_key' => $license->license_key,
            'status' => $license->status,
            'tier' => $license->tier,
            'plan_type' => $license->plan->type,
            'is_trial' => $license->isTrial(),
            'is_lifetime' => $license->isLifetime(),
            'expires_at' => $license->expires_at?->toIso8601String(),
            'days_left' => $license->daysLeft(),
            'max_activations' => $license->max_activations,
            'activations_used' => $license->activations()->count(),
            'fingerprint' => $activation->fingerprint,
            'customer' => $license->customer->name,
            'customer_email' => $license->customer->email,
            'issued_at' => now()->unix(),
        ];

        return response()->json([
            'ok' => true,
            ...$this->signatures->sign($payload),
        ]);
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
