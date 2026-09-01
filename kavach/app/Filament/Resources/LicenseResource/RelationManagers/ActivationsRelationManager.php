<?php

namespace App\Filament\Resources\LicenseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ActivationsRelationManager extends RelationManager
{
    protected static string $relationship = 'activations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('fingerprint')
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('fingerprint')
                    ->limit(24)
                    ->copyable(),
                Tables\Columns\TextColumn::make('ip'),
                Tables\Columns\TextColumn::make('app_version'),
                Tables\Columns\TextColumn::make('last_check_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Deactivate')
                    ->modalHeading('Deactivate this installation?')
                    ->modalDescription('Frees one activation slot so the key can be used on another installation.'),
            ]);
    }
}
