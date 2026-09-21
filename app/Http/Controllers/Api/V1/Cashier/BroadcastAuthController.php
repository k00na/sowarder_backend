<?php

namespace App\Http\Controllers\Api\V1\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

/**
 * Authorizes a cashier device (Sanctum bearer token) onto its restaurant's
 * private Reverb channel. Deliberately placed under `/api/v1/cashier` and
 * guarded the same way as every other cashier route, rather than relying on
 * Laravel's cookie-based default `/broadcasting/auth` route — cashier
 * devices authenticate with a bearer token, not a browser session.
 */
class BroadcastAuthController extends Controller
{
    /**
     * Named `authenticate` (not `authorize`) to avoid shadowing
     * `AuthorizesRequests::authorize()` inherited from the base Controller.
     *
     * @return mixed the broadcaster-specific auth payload (Laravel's own
     *               `BroadcastController::authenticate()` returns the same
     *               untyped `Broadcast::auth()` result)
     */
    public function authenticate(Request $request): mixed
    {
        return Broadcast::auth($request);
    }
}
