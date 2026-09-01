<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activation extends Model
{
    protected $fillable = [
        'license_id', 'fingerprint', 'label', 'ip', 'app_version', 'last_check_at',
    ];

    protected $casts = [
        'last_check_at' => 'datetime',
    ];

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
