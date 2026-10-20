<?php

use Database\Seeders\DemoRestaurantSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sowarder:demo-harbor-a1-token', function () {
    $table = DemoRestaurantSeeder::assignHarborGrillA1DemoToken();

    if ($table === null) {
        $this->error('Harbor Grill A1 not found. Seed demo restaurants first: ./vendor/bin/sail artisan db:seed');

        return Command::FAILURE;
    }

    $this->info('Harbor Grill A1 token is test.');
    $this->line('Desktop: http://localhost:5173/test');
    $this->line('Phone:   http://<vite-network-ip>:5173/test');

    return Command::SUCCESS;
})->purpose('Set Harbor Grill A1 QR token to test for local guest-PWA demo');
