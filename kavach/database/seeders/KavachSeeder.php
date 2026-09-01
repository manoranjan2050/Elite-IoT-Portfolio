<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Services\LicenseKeyService;
use Illuminate\Database\Seeder;

class KavachSeeder extends Seeder
{
    public function run(): void
    {
        $openpharma = Product::firstOrCreate(
            ['slug' => 'openpharma'],
            [
                'name' => 'OpenPharma',
                'key_prefix' => 'OPHR',
                'description' => 'Pharmacy ERP — POS, GST billing, inventory, returns, reports.',
            ],
        );

        $plans = [
            ['name' => 'Free Trial (30 days)', 'type' => 'trial', 'tier' => 'pro', 'price' => 0, 'duration_days' => 30],
            ['name' => 'Monthly Normal', 'type' => 'monthly', 'tier' => 'normal', 'price' => 499, 'duration_days' => 30],
            ['name' => 'Monthly Pro', 'type' => 'monthly', 'tier' => 'pro', 'price' => 999, 'duration_days' => 30],
            ['name' => 'Lifetime Normal', 'type' => 'lifetime', 'tier' => 'normal', 'price' => 4999, 'duration_days' => null],
            ['name' => 'Lifetime Pro', 'type' => 'lifetime', 'tier' => 'pro', 'price' => 9999, 'duration_days' => null],
        ];

        foreach ($plans as $plan) {
            $openpharma->plans()->firstOrCreate(['name' => $plan['name']], $plan);
        }

        $customer = Customer::firstOrCreate(
            ['email' => 'demo@example.com'],
            ['name' => 'Demo Customer', 'phone' => '9999999999'],
        );

        $trialPlan = $openpharma->plans()->where('type', 'trial')->first();

        if (! $customer->licenses()->where('product_id', $openpharma->id)->exists()) {
            $customer->licenses()->create([
                'product_id' => $openpharma->id,
                'plan_id' => $trialPlan->id,
                'license_key' => app(LicenseKeyService::class)->generate($openpharma),
                'status' => 'active',
                'tier' => $trialPlan->tier,
                'expires_at' => now()->addDays($trialPlan->duration_days),
                'max_activations' => $trialPlan->max_activations,
                'notes' => 'Demo trial license created by seeder.',
            ]);
        }
    }
}
