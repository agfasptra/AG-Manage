<?php

namespace App\Policies;

use App\Models\GoalContribution;
use App\Models\User;

class GoalContributionPolicy
{
    /**
     * Determine whether the user can delete the contribution.
     */
    public function delete(User $user, GoalContribution $contribution): bool
    {
        return $user->id === $contribution->user_id;
    }
}
