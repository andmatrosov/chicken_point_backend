<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdminAccessSafetyService
{
    public function assertAdminDemotionAllowed(
        User $target,
        bool $wasAdmin,
        bool $newIsAdmin,
        ?User $actor = null,
        bool $lockAdminRows = false,
    ): void {
        if (! $wasAdmin || $newIsAdmin) {
            return;
        }

        $adminIdsQuery = User::query()
            ->where('is_admin', true)
            ->orderBy('id');

        if ($lockAdminRows) {
            $adminIdsQuery->lockForUpdate();
        }

        $adminIds = $adminIdsQuery->pluck('id');

        if ($adminIds->contains($target->getKey()) && $adminIds->count() <= 1) {
            throw ValidationException::withMessages([
                'is_admin' => ['At least one admin must remain in the system.'],
            ]);
        }

        if ($actor?->is($target)) {
            throw ValidationException::withMessages([
                'is_admin' => ['You cannot remove your own admin access.'],
            ]);
        }
    }
}
