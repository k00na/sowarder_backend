<?php

namespace App\Filament\Resources\Restaurants\Schemas;

use App\Enums\Currency;
use App\Enums\RestaurantStatus;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old): void {
                        $currentSlug = $get('slug');
                        if (blank($currentSlug) || $currentSlug === Str::slug((string) $old)) {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Used in the tenant panel URL: /app/{slug}'),
                Select::make('status')
                    ->options(RestaurantStatus::class)
                    ->default(RestaurantStatus::Enabled)
                    ->required()
                    ->helperText('Disabled = immediate hard cut for guests, cashiers, and tenant panel.'),
                Select::make('currency')
                    ->options(Currency::class)
                    ->default(Currency::Mvr)
                    ->required(),
                TextInput::make('timezone')
                    ->required()
                    ->default('Indian/Maldives')
                    ->helperText('IANA timezone, e.g. Indian/Maldives'),
                ColorPicker::make('primary_color')
                    ->required()
                    ->default('#0F766E'),
                ColorPicker::make('secondary_color')
                    ->required()
                    ->default('#134E4A'),
            ]);
    }
}
