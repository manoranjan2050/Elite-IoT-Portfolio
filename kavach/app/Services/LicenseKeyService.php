<?php

namespace App\Services;

use App\Models\License;
use App\Models\Product;

class LicenseKeyService
{
    /** Unambiguous charset: no 0/O, 1/I/L to keep keys phone-friendly. */
    private const CHARSET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /** Generate a unique key like OPHR-7K2M-XJ4P-Q8RT-2NWD */
    public function generate(Product $product): string
    {
        do {
            $groups = [];
            for ($g = 0; $g < 4; $g++) {
                $chunk = '';
                for ($c = 0; $c < 4; $c++) {
                    $chunk .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
                }
                $groups[] = $chunk;
            }
            $key = strtoupper($product->key_prefix).'-'.implode('-', $groups);
        } while (License::where('license_key', $key)->exists());

        return $key;
    }
}
