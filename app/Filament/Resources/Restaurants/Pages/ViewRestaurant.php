<?php

namespace App\Filament\Resources\Restaurants\Pages;

use App\Filament\Resources\Restaurants\RestaurantResource;
use App\Services\OwnerInviteService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;

class ViewRestaurant extends ViewRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->inviteOwnerAction(),
        ];
    }

    protected function inviteOwnerAction(): Action
    {
        return Action::make('inviteOwner')
            ->label('Invite owner')
            ->icon('heroicon-o-envelope')
            ->form([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->default(fn (): ?string => $this->record->owner?->name),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->default(fn (): ?string => $this->record->owner?->email),
            ])
            ->action(function (array $data): void {
                try {
                    app(OwnerInviteService::class)->invite(
                        $this->record,
                        $data['email'],
                        $data['name'],
                    );
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Invite failed')
                        ->body(collect($exception->errors())->flatten()->first() ?? $exception->getMessage())
                        ->send();

                    throw $exception;
                }

                Notification::make()
                    ->success()
                    ->title('Owner invite sent')
                    ->body('Password setup email queued for '.$data['email'])
                    ->send();
            });
    }
}
