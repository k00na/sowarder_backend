<?php

namespace App\Filament\App\Resources\DiningTables\Tables;

use App\Filament\App\Resources\DiningTables\DiningTableResource;
use App\Models\DiningTable;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class DiningTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('label')
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('token')
                    ->label('QR token')
                    ->limit(12)
                    ->copyable()
                    ->tooltip(fn (DiningTable $record): string => $record->token),
                TextColumn::make('deleted_at')
                    ->label('Archived')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('regenerateToken')
                    ->label('Regenerate token')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate QR token?')
                    ->modalDescription('Every printed QR code for this table will stop working immediately, including mid-service. Guests scanning an old code will see an invalid-table error until you print and place the new code.')
                    ->modalSubmitActionLabel('Regenerate token')
                    ->action(function (DiningTable $record): void {
                        $record->regenerateToken();

                        Notification::make()
                            ->success()
                            ->title('Token regenerated')
                            ->body('Print and place the new QR code as soon as possible.')
                            ->send();
                    }),
                Action::make('downloadQrPng')
                    ->label('Download QR (PNG)')
                    ->icon(Heroicon::OutlinedPhoto)
                    ->color('gray')
                    ->url(fn (DiningTable $record): string => route('filament.app.dining-tables.qr.png', [
                        'tenant' => Filament::getTenant(),
                        'diningTable' => $record,
                    ])),
                Action::make('downloadQrPdf')
                    ->label('Download QR (PDF)')
                    ->icon(Heroicon::OutlinedDocument)
                    ->color('gray')
                    ->url(fn (DiningTable $record): string => route('filament.app.dining-tables.qr.pdf', [
                        'tenant' => Filament::getTenant(),
                        'diningTable' => $record,
                    ])),
                Action::make('previewMenu')
                    ->label('Preview menu')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->url(fn (DiningTable $record): string => route('filament.app.guest-preview.tables.show', [
                        'tenant' => Filament::getTenant(),
                        'diningTable' => $record,
                    ]))
                    ->openUrlInNewTab(),
                DiningTableResource::guardedDeleteAction(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, Action $action): void {
                            $hasOpenSession = $records->contains(
                                fn (DiningTable $table): bool => $table->openSession()->exists(),
                            );

                            if ($hasOpenSession) {
                                Notification::make()
                                    ->danger()
                                    ->title("Can't delete tables")
                                    ->body('One or more selected tables has an open session. End the session before deleting it.')
                                    ->send();

                                $action->halt();
                            }
                        }),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
