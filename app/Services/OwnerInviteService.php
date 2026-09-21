<?php

namespace App\Services;

use App\Enums\PlatformRole;
use App\Models\Restaurant;
use App\Models\User;
use App\Notifications\OwnerInviteNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OwnerInviteService
{
    /**
     * Create or update the restaurant owner and send a password-setup invite.
     *
     * @throws ValidationException
     */
    public function invite(Restaurant $restaurant, string $email, string $name): User
    {
        $email = Str::lower(trim($email));

        return DB::transaction(function () use ($restaurant, $email, $name): User {
            $existing = User::query()->where('email', $email)->first();

            if ($existing !== null) {
                if ($existing->isPlatformSuperAdmin()) {
                    throw ValidationException::withMessages([
                        'email' => ['Cannot invite a platform super-admin as a restaurant owner.'],
                    ]);
                }

                if ($existing->restaurant_id !== null && $existing->restaurant_id !== $restaurant->id) {
                    throw ValidationException::withMessages([
                        'email' => ['This email already belongs to another restaurant.'],
                    ]);
                }
            }

            $currentOwner = $restaurant->owner()->first();

            if ($currentOwner !== null && ($existing === null || $currentOwner->isNot($existing))) {
                throw ValidationException::withMessages([
                    'email' => ['This restaurant already has an owner. Invite that owner to resend, or use their email.'],
                ]);
            }

            $owner = User::query()->firstOrNew(['email' => $email]);
            $owner->fill([
                'name' => $name,
                'restaurant_id' => $restaurant->id,
                'platform_role' => PlatformRole::Owner,
                'email_verified_at' => null,
            ]);

            if (! $owner->exists) {
                $owner->password = Str::password(64);
            }

            $owner->save();
            $owner->notify(new OwnerInviteNotification($restaurant));

            return $owner->refresh();
        });
    }

    public function completePasswordSetup(User $user, string $password): User
    {
        if (! $user->isRestaurantOwner() || $user->restaurant_id === null) {
            abort(404);
        }

        $user->forceFill([
            'password' => $password,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user->refresh();
    }
}
