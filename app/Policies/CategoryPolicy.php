<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine whether the user can view the category.
     * Users can view their own categories or system defaults (user_id = null).
     */
    public function view(User $user, Category $category): bool
    {
        return $category->user_id === null || $user->id === $category->user_id;
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the category.
     * Only user-owned categories can be updated (not system defaults).
     */
    public function update(User $user, Category $category): bool
    {
        return $category->user_id !== null && $user->id === $category->user_id;
    }

    /**
     * Determine whether the user can toggle active status of the category.
     * Only user-owned categories can be toggled.
     */
    public function toggleActive(User $user, Category $category): bool
    {
        return $category->user_id !== null && $user->id === $category->user_id;
    }

    /**
     * Determine whether the user can delete the category.
     * Only if user-owned and no transactions reference it.
     */
    public function delete(User $user, Category $category): bool
    {
        return $category->user_id !== null
            && $user->id === $category->user_id
            && $category->transactions()->count() === 0;
    }
}
