<?php

namespace App\Filament\App\Resources\DiningTables\Pages;

use App\Filament\App\Resources\DiningTables\DiningTableResource;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditDiningTable extends EditRecord
{
    protected static string $resource = DiningTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DiningTableResource::guardedDeleteAction(),
            RestoreAction::make(),
        ];
    }
}
