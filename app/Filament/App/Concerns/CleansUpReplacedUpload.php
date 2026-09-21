<?php

namespace App\Filament\App\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Deletes a previously stored file once a `FileUpload` field has been
 * replaced with a new one, so orphaned uploads do not accumulate on disk.
 */
trait CleansUpReplacedUpload
{
    protected function deleteStoredFileIfReplaced(?string $previousPath, ?string $currentPath, string $disk = 'public'): void
    {
        if (
            filled($previousPath)
            && $previousPath !== $currentPath
            && Storage::disk($disk)->exists($previousPath)
        ) {
            Storage::disk($disk)->delete($previousPath);
        }
    }
}
