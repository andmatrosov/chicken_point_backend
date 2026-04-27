<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\AdminAccessSafetyService;
use App\Services\AdminActionLogService;
use App\Services\SuspiciousGameResultFlagService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

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
            Action::make('resetSuspicionPoints')
                ->label('Сбросить points')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Сбросить suspicion points?')
                ->modalDescription('Флаг пользователя останется без изменений. Для снятия permanent-флага используйте переключатель в форме.')
                ->visible(fn (): bool => (int) $this->record->suspicious_game_result_points > 0)
                ->action(function (): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        DB::transaction(function () use ($admin): void {
                            /** @var User $lockedRecord */
                            $lockedRecord = User::query()
                                ->lockForUpdate()
                                ->findOrFail($this->record->getKey());

                            $oldPoints = (int) $lockedRecord->suspicious_game_result_points;

                            app(SuspiciousGameResultFlagService::class)->resetPoints($lockedRecord);
                            $lockedRecord->refresh();

                            app(AdminActionLogService::class)->logUserSuspicionPointsReset(
                                $admin,
                                $lockedRecord,
                                $oldPoints,
                                (int) $lockedRecord->suspicious_game_result_points,
                            );

                            $this->record = $lockedRecord;
                        });

                        Notification::make()
                            ->success()
                            ->title('Suspicion points сброшены')
                            ->body('Points пользователя сброшены до 0. Флаг оставлен без изменений.')
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Не удалось сбросить suspicion points')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
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
            'has_suspicious_game_results' => (bool) $this->record->has_suspicious_game_results,
            'suspicious_game_result_points' => (int) $this->record->suspicious_game_result_points,
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data): Model {
            /** @var User $lockedRecord */
            $lockedRecord = User::query()
                ->lockForUpdate()
                ->findOrFail($record->getKey());

            /** @var User|null $actor */
            $actor = auth()->user();

            if (! ($actor instanceof User)) {
                $actor = null;
            }

            /** @var AdminAccessSafetyService $adminAccessSafetyService */
            $adminAccessSafetyService = app(AdminAccessSafetyService::class);

            $adminAccessSafetyService->assertAdminDemotionAllowed(
                target: $lockedRecord,
                wasAdmin: (bool) $lockedRecord->is_admin,
                newIsAdmin: (bool) ($data['is_admin'] ?? $lockedRecord->is_admin),
                actor: $actor,
                lockAdminRows: true,
            );

            /** @var SuspiciousGameResultFlagService $suspiciousGameResultFlagService */
            $suspiciousGameResultFlagService = app(SuspiciousGameResultFlagService::class);
            /** @var AdminActionLogService $adminActionLogService */
            $adminActionLogService = app(AdminActionLogService::class);

            $manualSuspiciousFlag = (bool) ($data['has_suspicious_game_results'] ?? $lockedRecord->has_suspicious_game_results);
            $manualSuspicionPoints = max(0, (int) ($data['suspicious_game_result_points'] ?? $lockedRecord->suspicious_game_result_points));
            $originalSuspicionPoints = (int) $lockedRecord->suspicious_game_result_points;
            unset($data['has_suspicious_game_results']);
            unset($data['suspicious_game_result_points']);

            $lockedRecord->fill($data);
            $lockedRecord->save();
            $suspiciousGameResultFlagService->setPoints($lockedRecord, $manualSuspicionPoints);
            $suspiciousGameResultFlagService->syncManualFlag($lockedRecord, $manualSuspiciousFlag);
            $lockedRecord->refresh();

            if ($actor instanceof User) {
                $adminActionLogService->logUserSuspicionPointsEdit(
                    $actor,
                    $lockedRecord,
                    $originalSuspicionPoints,
                    (int) $lockedRecord->suspicious_game_result_points,
                );
            }

            return $lockedRecord;
        });
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

        foreach (['email', 'best_score', 'is_admin', 'has_suspicious_game_results'] as $field) {
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
