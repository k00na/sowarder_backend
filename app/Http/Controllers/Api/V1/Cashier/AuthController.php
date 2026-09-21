<?php

namespace App\Http\Controllers\Api\V1\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cashier\LoginRequest;
use App\Models\User;
use App\Services\CashierAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly CashierAuthService $cashierAuth,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        ['user' => $user, 'token' => $token] = $this->cashierAuth->login(
            $request->string('email')->toString(),
            $request->string('cashier_password')->toString(),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'restaurant' => [
                'id' => $user->restaurant_id,
                'name' => $user->restaurant?->name,
                'currency' => $user->restaurant?->currency?->value,
                'timezone' => $user->restaurant?->timezone,
            ],
        ]);
    }

    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $this->cashierAuth->logout($user);

        return response()->noContent();
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('restaurant');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'restaurant' => [
                'id' => $user->restaurant_id,
                'name' => $user->restaurant?->name,
                'currency' => $user->restaurant?->currency?->value,
                'timezone' => $user->restaurant?->timezone,
                'enabled' => $user->restaurant?->isEnabled(),
            ],
        ]);
    }
}
