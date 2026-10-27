<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine whether the user can view the wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can create wallets.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the wallet.
     */
    public function update(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can archive/restore the wallet.
     */
    public function archive(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }

    /**
     * Determine whether the user can delete the wallet.
     * Only allowed if wallet has no transactions.
     */
    public function delete(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id
            && $wallet->transactions()->count() === 0
            && $wallet->incomingTransfers()->count() === 0;
    }
}
