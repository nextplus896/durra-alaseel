<?php

namespace App\Observers;

use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * Wallet creation is handled by the RegisteredUsers / LoggedInUsers traits.
     */
    public function created(User $user): void
    {
        // balance is stored in user_wallets — no action needed here
    }
}
