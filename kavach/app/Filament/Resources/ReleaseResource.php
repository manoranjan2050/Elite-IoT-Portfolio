<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReleaseResource\Pages;
use App\Models\Release;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReleaseResource extends Resource
{
    protected static ?string $model = Release::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Forms\Components\TextInput::make('version')
                    ->required()
                    ->placeholder('1.0.0')
                    ->helperText('Semantic version. Must be higher than previous releases to be offered as an update.'),
                Forms\Components\Select::make('channel')
                    ->options(['stable' => 'Stable', 'beta' => 'Beta'])
                    ->default('stable')
                    ->required(),
                Forms\Components\Select::make('min_tier')
                    ->label('Minimum tier')
                    ->options(['normal' => 'Normal', 'pro' => 'Pro'])
                    ->placeholder('All licenses'),
                Forms\Components\Textarea::make('changelog')
                    ->rows(6)
                    ->placeholder("- Added GST report export\n- Fixed billing rounding bug")
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('file_path')
                    ->label('Update package (.zip)')
                    ->disk('local')
                    ->directory('releases')
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                    ->maxSize(512000)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('released_at')
                    ->default(now()),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->helperText('Inactive releases are never offered to clients.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'stable' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('min_tier')
                    ->badge()
                    ->placeholder('All')
                    ->color('info'),
                Tables\Columns\TextColumn::make('file_size')
                    ->formatStateUsing(fn (?int $state): string => $state
                        ? number_format($state / 1048576, 1).' MB'
                        : '—'),
                Tables\Columns\TextColumn::make('downloads_count')
                    ->counts('downloads')
                    ->label('Downloads'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('released_at')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('released_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReleases::route('/'),
            'create' => Pages\CreateRelease::route('/create'),
            'edit' => Pages\EditRelease::route('/{record}/edit'),
        ];
    }
}
