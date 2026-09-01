<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\License;
use App\Models\Plan;
use App\Models\Product;
use App\Services\LicenseKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Token-secured admin endpoint that lets trusted apps (e.g. the MTP Suite
 * store) issue licenses programmatically instead of manually via Filament.
 *
 * POST /api/admin/license
 * { product, plan, tier?, email, name?, days?, max_activations? }
 *   product : product slug (e.g. "openpharma")
 *   plan    : plan type — trial | monthly | yearly | lifetime
 *   tier    : normal | pro (default: normal)
 *   email   : customer email (customer is created if unknown)
 *   name    : customer name (used when creating the customer)
 *   days    : override validity in days (default: the plan's duration_days)
 * -> { ok, license_key, expires_at, ... }
 */
class AdminLicenseApiController extends Controller
{
    public function issue(Request $request, LicenseKeyService $keys): JsonResponse
    {
        $data = $request->validate([
            'product' => 'required|string',
            'plan' => 'required|string|in:trial,monthly,yearly,lifetime',
            'tier' => 'nullable|string|in:normal,pro',
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'days' => 'nullable|integer|min:1|max:36500',
            'max_activations' => 'nullable|integer|min:1|max:100',
        ]);

        $product = Product::where('slug', $data['product'])->where('is_active', true)->first();
        if (! $product) {
            return response()->json(['ok' => false, 'error' => 'unknown_product'], 422);
        }

        $tier = $data['tier'] ?? 'normal';

        $plan = Plan::where('product_id', $product->id)
            ->where('type', $data['plan'])
            ->where('tier', $tier)
            ->where('is_active', true)
            ->first()
            // fall back to any active plan of that type so a missing tier
            // variant doesn't block issuing (tier is stored on the license)
            ?? Plan::where('product_id', $product->id)
                ->where('type', $data['plan'])
                ->where('is_active', true)
                ->first();

        if (! $plan) {
            return response()->json(['ok' => false, 'error' => 'no_matching_plan'], 422);
        }

        $customer = Customer::firstOrCreate(
            ['email' => strtolower(trim($data['email']))],
            ['name' => $data['name'] ?? strstr($data['email'], '@', true)]
        );

        $days = $data['days'] ?? $plan->duration_days;
        $expiresAt = $days ? now()->addDays((int) $days) : null; // null = lifetime

        $license = License::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'plan_id' => $plan->id,
            'license_key' => $keys->generate($product),
            'status' => 'active',
            'tier' => $tier,
            'expires_at' => $expiresAt,
            'max_activations' => $data['max_activations'] ?? $plan->max_activations,
            'notes' => 'Issued via admin API ('.$request->ip().')',
        ]);

        return response()->json([
            'ok' => true,
            'license_key' => $license->license_key,
            'product' => $product->slug,
            'plan' => $plan->type,
            'tier' => $license->tier,
            'email' => $customer->email,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'days_left' => $license->daysLeft(),
            'max_activations' => $license->max_activations,
        ], 201);
    }
}
