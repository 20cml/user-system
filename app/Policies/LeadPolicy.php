<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Only the agent who created a lead may view or update it.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $lead->user_id === $user->id;
    }

    /**
     * Only the agent who created a lead may delete it.
     */
    public function delete(User $user, Lead $lead): bool
    {
        return $lead->user_id === $user->id;
    }
}
