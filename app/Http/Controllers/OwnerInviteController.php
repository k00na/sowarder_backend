<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OwnerInviteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class OwnerInviteController extends Controller
{
    public function show(User $user): View
    {
        abort_unless($user->isRestaurantOwner(), 404);

        $user->loadMissing('restaurant');

        return view('owner-invite.set-password', [
            'user' => $user,
            'restaurant' => $user->restaurant,
        ]);
    }

    public function store(Request $request, User $user, OwnerInviteService $invites): RedirectResponse
    {
        abort_unless($user->isRestaurantOwner(), 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $invites->completePasswordSetup($user, $validated['password']);

        return redirect()
            ->to(url('/app/login'))
            ->with('status', 'Password set. You can sign in to your restaurant panel.');
    }
}
