<?php

namespace App\Filament\App\Resources\Products\Pages;

use App\Filament\App\Concerns\CleansUpReplacedUpload;
use App\Filament\App\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use CleansUpReplacedUpload;

    protected static string $resource = ProductResource::class;

    protected ?string $previousImagePath = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Product $product */
        $product = $this->record;

        $this->previousImagePath = $product->image_path;

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var Product $product */
        $product = $this->record->fresh();

        $this->deleteStoredFileIfReplaced($this->previousImagePath, $product->image_path);
        $this->previousImagePath = null;
    }
}
