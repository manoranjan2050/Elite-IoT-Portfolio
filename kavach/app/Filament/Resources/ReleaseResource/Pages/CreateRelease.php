<?php

namespace App\Filament\Resources\ReleaseResource\Pages;

use App\Filament\Resources\ReleaseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateRelease extends CreateRecord
{
    protected static string $resource = ReleaseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $absolute = Storage::path($data['file_path']);
        $data['file_hash'] = hash_file('sha256', $absolute);
        $data['file_size'] = filesize($absolute);
        $data['released_at'] ??= now();

        return $data;
    }
}
