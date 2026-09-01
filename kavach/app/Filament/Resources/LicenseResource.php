<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LicenseResource\Pages;
use App\Filament\Resources\LicenseResource\RelationManagers;
use App\Models\License;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('email')->email()->required(),
                        Forms\Components\TextInput::make('phone'),
                    ])
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->live()
                    ->required(),
                Forms\Components\Select::make('plan_id')
                    ->label('Plan')
                    ->options(fn (Forms\Get $get) => \App\Models\Plan::query()
                        ->where('product_id', $get('product_id'))
                        ->where('is_active', true)
                        ->pluck('name', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('license_key')
                    ->helperText('Leave empty to auto-generate.')
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Select::make('tier')
                    ->options(['normal' => 'Normal', 'pro' => 'Pro'])
                    ->placeholder('Auto (same as plan)'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->helperText('Leave empty: auto from plan duration. Lifetime plans stay empty.'),
                Forms\Components\TextInput::make('max_activations')
                    ->numeric()
                    ->placeholder('Auto (same as plan)'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('license_key')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'warning',
                        'suspended' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('tier')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'pro' ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime('d M Y')
                    ->placeholder('Lifetime')
                    ->sortable(),
                Tables\Columns\TextColumn::make('activations_count')
                    ->counts('activations')
                    ->label('Used')
                    ->formatStateUsing(fn (License $record, $state) => "{$state} / {$record->max_activations}"),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                    ]),
                Tables\Filters\SelectFilter::make('tier')
                    ->options(['normal' => 'Normal', 'pro' => 'Pro']),
            ])
            ->actions([
                Tables\Actions\Action::make('extend')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('days')
                            ->numeric()
                            ->required()
                            ->default(30)
                            ->helperText('Days to add. Expired licenses restart from today and are re-activated.'),
                    ])
                    ->action(function (License $record, array $data): void {
                        $base = ($record->expires_at && $record->expires_at->isFuture())
                            ? $record->expires_at
                            : now();
                        $record->update([
                            'expires_at' => $base->copy()->addDays((int) $data['days']),
                            'status' => 'active',
                        ]);
                    })
                    ->visible(fn (License $record): bool => ! $record->isLifetime()),
                Tables\Actions\Action::make('suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (License $record) => $record->update(['status' => 'suspended']))
                    ->visible(fn (License $record): bool => $record->status === 'active'),
                Tables\Actions\Action::make('resume')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (License $record) => $record->update(['status' => 'active']))
                    ->visible(fn (License $record): bool => $record->status === 'suspended'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLicenses::route('/'),
            'create' => Pages\CreateLicense::route('/create'),
            'edit' => Pages\EditLicense::route('/{record}/edit'),
        ];
    }
}
