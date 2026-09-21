<?php

namespace App\Providers;

use App\Models\DiningTable;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('diningTable', function (string $value): DiningTable {
            $query = DiningTable::query()->whereKey($value);

            /** @var User|null $user */
            $user = auth('sanctum')->user() ?? auth()->user();

            if ($user !== null && ! $user->isPlatformSuperAdmin()) {
                $query->where('restaurant_id', $user->restaurant_id);
            }

            return $query->firstOrFail();
        });

        Route::bind('tableSession', function (string $value): TableSession {
            $query = TableSession::query()->whereKey($value);

            /** @var User|null $user */
            $user = auth('sanctum')->user() ?? auth()->user();

            if ($user !== null && ! $user->isPlatformSuperAdmin()) {
                $query->where('restaurant_id', $user->restaurant_id);
            }

            return $query->firstOrFail();
        });

        $this->configureGuestRateLimiters();
    }

    /**
     * ~10 order submits/minute per table token, ~30/minute per IP; a lighter
     * per-IP limit on menu reads to discourage scraping without blocking
     * normal guest browsing.
     */
    private function configureGuestRateLimiters(): void
    {
        RateLimiter::for('guest-order', function (Request $request): array {
            return [
                Limit::perMinute(10)->by('guest-order-table:'.$request->route('table_token')),
                Limit::perMinute(30)->by('guest-order-ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('guest-menu', function (Request $request): Limit {
            return Limit::perMinute(60)->by('guest-menu-ip:'.$request->ip());
        });
    }
}
