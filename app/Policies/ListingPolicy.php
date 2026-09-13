<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /**
     * Only the agent who created a listing may view or update it.
     */
    public function update(User $user, Listing $listing): bool
    {
        return $listing->user_id === $user->id;
    }

    /**
     * Only the agent who created a listing may delete it.
     */
    public function delete(User $user, Listing $listing): bool
    {
        return $listing->user_id === $user->id;
    }
}
