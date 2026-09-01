<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class License extends Model
{
    protected $fillable = [
        'customer_id', 'product_id', 'plan_id', 'license_key',
        'status', 'tier', 'expires_at', 'max_activations', 'notes',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function activations(): HasMany
    {
        return $this->hasMany(Activation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isLifetime(): bool
    {
        return $this->expires_at === null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isTrial(): bool
    {
        return $this->plan?->type === 'trial';
    }

    public function daysLeft(): ?int
    {
        if ($this->isLifetime()) {
            return null;
        }

        return max(0, (int) ceil(now()->diffInDays($this->expires_at, false)));
    }

    /** Flip status to expired when past expiry; returns fresh status. */
    public function syncStatus(): string
    {
        if ($this->status === 'active' && $this->isExpired()) {
            $this->update(['status' => 'expired']);
        }

        return $this->status;
    }
}
