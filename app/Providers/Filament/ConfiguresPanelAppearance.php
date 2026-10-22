<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\Support\Colors\Color;
use Illuminate\Contracts\Support\Htmlable;

final class ConfiguresPanelAppearance
{
    public static function apply(Panel $panel): Panel
    {
        return $panel
            ->colors([
                'primary' => Color::hex('#0056c5'),
            ])
            ->darkMode(false)
            ->brandName('Sowader')
            ->brandLogo(fn (): Htmlable => view('filament.brand-logo'))
            ->brandLogoHeight('2rem')
            ->serifFont('Plus Jakarta Sans')
            ->viteTheme('resources/css/filament/theme.css');
    }
}
