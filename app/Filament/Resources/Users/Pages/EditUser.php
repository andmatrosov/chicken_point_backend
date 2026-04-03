<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\AdminActionLogService;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @var array<string, mixed>
     */
    protected array $originalAuditState = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->originalAuditState = [
            'email' => (string) $this->record->email,
            'coins' => (int) $this->record->coins,
            'best_score' => (int) $this->record->best_score,
            'is_admin' => (bool) $this->record->is_admin,
        ];
    }

    protected function afterSave(): void
    {
        /** @var User $admin */
        $admin = auth()->user();

        /** @var AdminActionLogService $adminActionLogService */
        $adminActionLogService = app(AdminActionLogService::class);

        $adminActionLogService->logUserBalanceEdit(
            $admin,
            $this->record,
            (int) ($this->originalAuditState['coins'] ?? $this->record->coins),
            (int) $this->record->coins,
        );

        $changes = [];

        foreach (['email', 'best_score', 'is_admin'] as $field) {
            $before = $this->originalAuditState[$field] ?? null;
            $after = $this->record->getAttribute($field);

            if ($before !== $after) {
                $changes[$field] = [
                    'old' => $before,
                    'new' => $after,
                ];
            }
        }

        $adminActionLogService->logUserDataUpdate(
            $admin,
            $this->record,
            $changes,
            filled($this->data['password'] ?? null),
        );
    }
}
