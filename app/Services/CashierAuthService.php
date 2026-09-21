<?php

namespace App\Services;

use App\Enums\RestaurantStatus;
use App\Exceptions\DomainException;
use App\Exceptions\RestaurantDisabledException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CashierAuthService
{
    /**
     * Authenticate with email + cashier password and issue a Sanctum token.
     *
     * Uses only `cashier_password` — never the Filament panel `password` field.
     *
     * @return array{user: User, token: NewAccessToken}
     *
     * @throws ValidationException
     * @throws RestaurantDisabledException
     * @throws DomainException
     */
    public function login(string $email, string $cashierPassword): array
    {
        $user = User::query()
            ->with('restaurant')
            ->where('email', $email)
            ->first();

        if ($user === null || blank($user->cashier_password) || ! Hash::check($cashierPassword, $user->cashier_password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid cashier credentials.'],
            ]);
        }

        if (! $user->isRestaurantOwner()) {
            throw new DomainException(
                'forbidden',
                'Cashier access is limited to restaurant owners.',
                Response::HTTP_FORBIDDEN,
            );
        }

        if ($user->restaurant === null || $user->restaurant->status === RestaurantStatus::Disabled) {
            throw new RestaurantDisabledException;
        }

        $token = $user->createToken(
            User::CASHIER_TOKEN_NAME,
            [User::CASHIER_TOKEN_ABILITY],
        );

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();

            return;
        }

        // Fallback when the guard did not attach a PersonalAccessToken instance.
        $user->tokens()->where('name', User::CASHIER_TOKEN_NAME)->delete();
    }
}
