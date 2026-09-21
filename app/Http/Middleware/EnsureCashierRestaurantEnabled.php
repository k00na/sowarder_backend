<?php

namespace App\Http\Middleware;

use App\Enums\RestaurantStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashierRestaurantEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'error' => [
                    'code' => 'unauthorized',
                    'message' => 'Unauthenticated.',
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (! $user->tokenCan(User::CASHIER_TOKEN_ABILITY)) {
            return response()->json([
                'error' => [
                    'code' => 'forbidden',
                    'message' => 'Cashier token required.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        $user->loadMissing('restaurant');

        if ($user->restaurant === null || $user->restaurant->status === RestaurantStatus::Disabled) {
            return response()->json([
                'error' => [
                    'code' => 'restaurant_disabled',
                    'message' => 'This restaurant is currently disabled.',
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
