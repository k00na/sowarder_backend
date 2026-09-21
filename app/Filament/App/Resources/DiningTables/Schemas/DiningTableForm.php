<?php

namespace App\Filament\App\Resources\DiningTables\Schemas;

use App\Models\DiningTable;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DiningTableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Renaming a table does not change its QR token.'),
                TextInput::make('token')
                    ->label('QR token')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?DiningTable $record): bool => $record !== null)
                    ->helperText('System-generated. Use "Regenerate token" from the table list to invalidate old QR codes.'),
            ]);
    }
}
