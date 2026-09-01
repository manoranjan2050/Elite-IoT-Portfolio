<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Release extends Model
{
    protected $fillable = [
        'product_id', 'version', 'channel', 'min_tier', 'changelog',
        'file_path', 'file_hash', 'file_size', 'is_active', 'released_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'released_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(UpdateDownload::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Whether a license of the given tier may receive this release. */
    public function availableForTier(string $tier): bool
    {
        return $this->min_tier === null
            || $this->min_tier === 'normal'
            || $tier === 'pro';
    }
}
