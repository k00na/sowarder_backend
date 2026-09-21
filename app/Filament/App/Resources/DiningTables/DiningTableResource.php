<?php

namespace App\Filament\App\Resources\DiningTables;

use App\Filament\App\Resources\DiningTables\Pages\CreateDiningTable;
use App\Filament\App\Resources\DiningTables\Pages\EditDiningTable;
use App\Filament\App\Resources\DiningTables\Pages\ListDiningTables;
use App\Filament\App\Resources\DiningTables\Schemas\DiningTableForm;
use App\Filament\App\Resources\DiningTables\Tables\DiningTablesTable;
use App\Models\DiningTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class DiningTableResource extends Resource
{
    protected static ?string $model = DiningTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Tables';

    protected static ?string $navigationLabel = 'Tables';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return DiningTableForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DiningTablesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDiningTables::route('/'),
            'create' => CreateDiningTable::route('/create'),
            'edit' => EditDiningTable::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    /**
     * A `DeleteAction` that stays visible (ownership is the only policy gate)
     * but halts with a clear notification when the table has an open
     * session, instead of silently hiding the button.
     */
    public static function guardedDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->before(function (DiningTable $record, Action $action): void {
                if ($record->openSession()->exists()) {
                    Notification::make()
                        ->danger()
                        ->title("Can't delete table")
                        ->body('This table has an open session. End the session before deleting it.')
                        ->send();

                    $action->halt();
                }
            });
    }
}
