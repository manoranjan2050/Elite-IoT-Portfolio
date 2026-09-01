<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdateDownload extends Model
{
    protected $fillable = ['release_id', 'license_id', 'ip', 'from_version'];

    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
