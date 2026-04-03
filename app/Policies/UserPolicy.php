<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewProfile(User $actor, User $subject): bool
    {
        return $actor->is($subject);
    }

    public function updateProfile(User $actor, User $subject): bool
    {
        return $actor->is($subject);
    }

    public function viewPrizes(User $actor, User $subject): bool
    {
        return $actor->is($subject);
    }
}
