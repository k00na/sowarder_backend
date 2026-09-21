<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Owner invite expiry
    |--------------------------------------------------------------------------
    |
    | Signed password-setup links expire after this many hours. Open P1 on
    | transactional email provider remains deferred; Mailpit covers local.
    |
    */
    'owner_invite_expiry_hours' => (int) env('SOWARDER_OWNER_INVITE_EXPIRY_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Guest menu base URL
    |--------------------------------------------------------------------------
    |
    | Encoded into table QR codes as `{base}/{token}`. Local PWA default is
    | Vite on Sail port 5173 until Stage 3 ships a deployed guest origin.
    |
    */
    'guest_menu_base_url' => rtrim((string) env('SOWARDER_GUEST_MENU_BASE_URL', 'http://localhost:5173'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Image uploads (logo + product)
    |--------------------------------------------------------------------------
    |
    | Store on the `public` disk (`storage/app/public`) so guest menu URLs can
    | resolve via `/storage/...` after `php artisan storage:link`. Pilot may
    | switch FILESYSTEM / AWS_* to S3 later without changing these limits.
    |
    */
    'image_max_kb' => (int) env('SOWARDER_IMAGE_MAX_KB', 2048),

    'image_allowed_mimes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SOWARDER_IMAGE_ALLOWED_MIMES', 'jpg,jpeg,png,webp')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | QR / printable table labels
    |--------------------------------------------------------------------------
    |
    | PNG size is the QR module canvas in pixels. PDF downloads use A4 portrait
    | with one table (label + QR) per page via barryvdh/laravel-dompdf.
    | Error correction level M — no restaurant logo embedded in the QR (P2).
    |
    */
    'qr_png_size' => (int) env('SOWARDER_QR_PNG_SIZE', 400),

    'qr_pdf_paper' => 'a4',

];
