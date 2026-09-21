<?php

namespace App\Filament\App\Resources\Products\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        $maxKb = (int) config('sowarder.image_max_kb');
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
        ];
        $acceptedMimes = collect(config('sowarder.image_allowed_mimes', []))
            ->map(fn (string $ext): ?string => $mimeMap[strtolower($ext)] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('menu_category_id')
                    ->label('Category')
                    ->relationship(
                        name: 'menuCategory',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => $query
                            ->where('restaurant_id', Filament::getTenant()?->getKey()),
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('price_minor')
                    ->label('Price')
                    ->prefix(fn (): ?string => Filament::getTenant()?->currency?->value)
                    ->numeric()
                    ->step('0.01')
                    ->minValue(0)
                    ->required()
                    ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                        $component->state($state === null ? null : $state / 100);
                    })
                    ->dehydrateStateUsing(fn (mixed $state): int => (int) round(((float) $state) * 100))
                    ->helperText('Enter the price in major units, e.g. 12.50.'),
                Toggle::make('is_available')
                    ->label('Available')
                    ->default(true),
                Textarea::make('description')
                    ->maxLength(2000)
                    ->columnSpanFull(),
                FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->disk('public')
                    ->directory('products/images')
                    ->visibility('public')
                    ->maxSize($maxKb)
                    ->acceptedFileTypes($acceptedMimes)
                    ->helperText('JPEG, PNG, or WebP. Max '.$maxKb.' KB.')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
