<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

class AccountOverview extends AccountWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';
}
