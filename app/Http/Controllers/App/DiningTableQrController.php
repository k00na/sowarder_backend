<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Http\Response as HttpResponse;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DiningTableQrController extends Controller
{
    /**
     * `$tenant` must stay as the leading, untyped parameter: the route is
     * `{tenant}/dining-tables/{diningTable}/...`, and Laravel's controller
     * dispatcher maps route parameters positionally. Dropping this
     * parameter (or type-hinting it as `Restaurant`, which would make
     * Laravel resolve it before Filament's own `IdentifyTenant` middleware
     * does) shifts `$diningTable` into the wrong argument slot. Read the
     * tenant via `Filament::getTenant()` instead.
     */
    public function png(string $tenant, DiningTable $diningTable): HttpResponse
    {
        $this->authorizeTableBelongsToTenant($diningTable);

        $png = (string) QrCode::format('png')
            ->size((int) config('sowarder.qr_png_size'))
            ->errorCorrection('M')
            ->generate($this->guestUrl($diningTable));

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="table-'.$diningTable->id.'-qr.png"',
        ]);
    }

    public function pdf(string $tenant, DiningTable $diningTable): HttpResponse
    {
        $this->authorizeTableBelongsToTenant($diningTable);

        $qrPng = (string) QrCode::format('png')
            ->size((int) config('sowarder.qr_png_size'))
            ->errorCorrection('M')
            ->generate($this->guestUrl($diningTable));

        return Pdf::loadView('dining-tables.qr-pdf', [
            'diningTable' => $diningTable,
            'qrBase64' => base64_encode($qrPng),
        ])
            ->setPaper((string) config('sowarder.qr_pdf_paper'), 'portrait')
            ->download('table-'.$diningTable->id.'-qr.pdf');
    }

    protected function authorizeTableBelongsToTenant(DiningTable $diningTable): void
    {
        abort_unless($diningTable->restaurant_id === Filament::getTenant()?->getKey(), 404);
    }

    protected function guestUrl(DiningTable $diningTable): string
    {
        return rtrim((string) config('sowarder.guest_menu_base_url'), '/').'/'.$diningTable->token;
    }
}
