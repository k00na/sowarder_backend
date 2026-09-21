<?php

namespace App\Filament\Resources\TableSessions\Pages;

use App\Filament\Resources\TableSessions\TableSessionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageTableSessions extends ManageRecords
{
    protected static string $resource = TableSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
