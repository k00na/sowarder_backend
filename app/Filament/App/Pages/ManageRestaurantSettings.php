<?php

namespace App\Filament\App\Pages;

use App\Enums\Currency;
use App\Models\Restaurant;
use App\Models\User;
use DateTimeZone;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ManageRestaurantSettings extends EditTenantProfile
{
    protected ?string $cashierPasswordToSet = null;

    protected ?string $previousLogoPath = null;

    public static function getLabel(): string
    {
        return 'Settings';
    }

    public function form(Schema $schema): Schema
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
                Section::make('Restaurant profile')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Slug is set by the platform admin and cannot be changed here.'),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->disk('public')
                            ->directory('restaurants/logos')
                            ->visibility('public')
                            ->maxSize($maxKb)
                            ->acceptedFileTypes($acceptedMimes)
                            ->helperText('JPEG, PNG, or WebP. Max '.$maxKb.' KB.'),
                        Select::make('timezone')
                            ->options(array_combine(
                                DateTimeZone::listIdentifiers(),
                                DateTimeZone::listIdentifiers(),
                            ))
                            ->searchable()
                            ->required(),
                        Select::make('currency')
                            ->options(Currency::class)
                            ->required()
                            ->helperText('Currency cannot change while any table session is open. Amounts are not auto-converted.'),
                        ColorPicker::make('primary_color')
                            ->required()
                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/'),
                        ColorPicker::make('secondary_color')
                            ->required()
                            ->regex('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})\b$/'),
                    ])
                    ->columns(2),
                Section::make('Cashier password')
                    ->description('Used by the cashier Flutter app only. Does not change your admin panel password.')
                    ->schema([
                        TextInput::make('cashier_password')
                            ->label('New cashier password')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->confirmed()
                            ->minLength(8)
                            ->dehydrated(fn (?string $state): bool => filled($state)),
                        TextInput::make('cashier_password_confirmation')
                            ->label('Confirm cashier password')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['cashier_password'], $data['cashier_password_confirmation']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Restaurant $restaurant */
        $restaurant = $this->tenant;

        $this->previousLogoPath = $restaurant->logo_path;
        $this->cashierPasswordToSet = $data['cashier_password'] ?? null;
        unset($data['cashier_password'], $data['cashier_password_confirmation']);

        $incomingCurrency = $data['currency'] ?? null;
        $incomingCurrencyValue = $incomingCurrency instanceof Currency
            ? $incomingCurrency->value
            : (string) $incomingCurrency;

        if (
            $incomingCurrencyValue !== $restaurant->currency->value
            && $restaurant->hasOpenTableSessions()
        ) {
            throw ValidationException::withMessages([
                'data.currency' => 'Currency cannot be changed while an open table session exists. Amounts are not auto-converted.',
            ]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Restaurant $restaurant */
        $restaurant = $this->tenant->fresh();

        if (
            filled($this->previousLogoPath)
            && $this->previousLogoPath !== $restaurant->logo_path
            && Storage::disk('public')->exists($this->previousLogoPath)
        ) {
            Storage::disk('public')->delete($this->previousLogoPath);
        }

        if (filled($this->cashierPasswordToSet)) {
            /** @var User $owner */
            $owner = auth()->user();
            $owner->update([
                'cashier_password' => $this->cashierPasswordToSet,
            ]);
        }

        $this->cashierPasswordToSet = null;
        $this->previousLogoPath = null;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Restaurant settings saved';
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title($this->getSavedNotificationTitle());
    }
}
