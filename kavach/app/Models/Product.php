<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['name', 'slug', 'key_prefix', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(Release::class);
    }
}
