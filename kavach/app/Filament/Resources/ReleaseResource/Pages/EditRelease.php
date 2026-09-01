<?php

namespace App\Filament\Resources\ReleaseResource\Pages;

use App\Filament\Resources\ReleaseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditRelease extends EditRecord
{
    protected static string $resource = ReleaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['file_path']) && Storage::exists($data['file_path'])) {
            $absolute = Storage::path($data['file_path']);
            $data['file_hash'] = hash_file('sha256', $absolute);
            $data['file_size'] = filesize($absolute);
        }

        return $data;
    }
}
