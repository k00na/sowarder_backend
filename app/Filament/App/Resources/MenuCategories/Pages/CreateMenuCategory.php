<?php

namespace App\Filament\App\Resources\MenuCategories\Pages;

use App\Filament\App\Resources\MenuCategories\MenuCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMenuCategory extends CreateRecord
{
    protected static string $resource = MenuCategoryResource::class;
}
