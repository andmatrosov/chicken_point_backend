<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class AdminActionLogService
{
    /**
     * @throws AuthorizationException
     */
    public function assertAdmin(User $admin): void
    {
        Gate::forUser($admin)->authorize('access-admin-panel');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function log(
        User $admin,
        string $action,
        string $entityType,
        int $entityId,
        array $payload = [],
    ): AdminActionLog {
        $this->assertAdmin($admin);

        return AdminActionLog::query()->create([
            'admin_user_id' => $admin->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'payload' => $payload,
        ]);
    }

    public function logUserBalanceEdit(
        User $admin,
        User $user,
        int $oldCoins,
        int $newCoins,
    ): ?AdminActionLog {
        if ($oldCoins === $newCoins) {
            return null;
        }

        return $this->log(
            $admin,
            'edit_user_balance',
            'user',
            $user->id,
            [
                'user_id' => $user->id,
                'changes' => [
                    'coins' => [
                        'old' => $oldCoins,
                        'new' => $newCoins,
                        'delta' => $newCoins - $oldCoins,
                    ],
                ],
            ],
        );
    }

    /**
     * @param  array<string, array<string, scalar|bool|null>>  $changes
     */
    public function logUserDataUpdate(
        User $admin,
        User $user,
        array $changes,
        bool $passwordChanged = false,
    ): ?AdminActionLog {
        if ($passwordChanged) {
            $changes['password'] = [
                'changed' => true,
            ];
        }

        if ($changes === []) {
            return null;
        }

        return $this->log(
            $admin,
            'update_user_admin_data',
            'user',
            $user->id,
            [
                'user_id' => $user->id,
                'changes' => $changes,
            ],
        );
    }
}
