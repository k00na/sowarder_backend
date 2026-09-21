<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Reset Sanctum/auth guard caches between HTTP calls so revoked tokens stay
     * revoked in API tests, while preserving the web session user for Filament.
     *
     * @param  array<string, mixed>  $parameters
     * @param  array<string, mixed>  $cookies
     * @param  array<string, mixed>  $files
     * @param  array<string, mixed>  $server
     */
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $webUser = auth()->guard('web')->user();

        $this->app['auth']->forgetGuards();

        if ($webUser instanceof Authenticatable) {
            $this->actingAs($webUser, 'web');
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    protected function actingAsPanelUser(User $user): static
    {
        $this->actingAs($user, 'web');

        session([
            'password_hash_web' => $user->getAuthPassword(),
        ]);

        return $this;
    }
}
