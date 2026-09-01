<?php

namespace App\Filament\Resources\LicenseResource\Pages;

use App\Filament\Resources\LicenseResource;
use App\Models\Plan;
use App\Models\Product;
use App\Services\LicenseKeyService;
use Filament\Resources\Pages\CreateRecord;

class CreateLicense extends CreateRecord
{
    protected static string $resource = LicenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $product = Product::findOrFail($data['product_id']);

        if (empty($data['license_key'])) {
            $data['license_key'] = app(LicenseKeyService::class)->generate($product);
        }

        $data['license_key'] = strtoupper(trim($data['license_key']));
        $data['tier'] = $data['tier'] ?? $plan->tier;
        $data['max_activations'] = $data['max_activations'] ?? $plan->max_activations;

        if (empty($data['expires_at']) && $plan->duration_days) {
            $data['expires_at'] = now()->addDays($plan->duration_days);
        }

        return $data;
    }
}
