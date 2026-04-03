<?php

namespace App\Filament\Pages;

use App\Services\LeaderboardService;
use App\Services\PrizeAutoAssignmentService;
use App\Models\User;
use App\Models\UserPrize;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class Leaderboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 70;

    protected string $view = 'filament.pages.leaderboard';

    public ?array $previewResult = null;

    public ?array $assignmentResult = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewPrizeAssignments')
                ->label('Preview Prize Assignments')
                ->icon('heroicon-o-eye')
                ->action(function (PrizeAutoAssignmentService $prizeAutoAssignmentService): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        $this->previewResult = $prizeAutoAssignmentService->previewCurrentLeaderboardAssignments($admin);

                        Notification::make()
                            ->success()
                            ->title('Preview generated')
                            ->body("{$this->previewResult['ready_count']} assignments are ready and {$this->previewResult['skipped_count']} entries have warnings.")
                            ->send();
                    } catch (AuthorizationException|Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Preview failed')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
            Action::make('autoAssignPrizes')
                ->label('Auto-Assign Prizes')
                ->icon('heroicon-o-gift')
                ->requiresConfirmation()
                ->action(function (PrizeAutoAssignmentService $prizeAutoAssignmentService): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        $this->assignmentResult = $prizeAutoAssignmentService->assignCurrentLeaderboardPrizes($admin);

                        Notification::make()
                            ->success()
                            ->title('Prize assignment completed')
                            ->body("Assigned {$this->assignmentResult['assigned_count']} entries and skipped {$this->assignmentResult['skipped_count']}.")
                            ->send();
                    } catch (AuthorizationException|Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Prize assignment failed')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRows(): array
    {
        $leaderboardEntries = app(LeaderboardService::class)->getTopEntries();
        $userIds = $leaderboardEntries->pluck('id')->all();
        $prizesByUser = UserPrize::query()
            ->with('prize')
            ->whereIn('user_id', $userIds)
            ->orderByDesc('assigned_at')
            ->get()
            ->groupBy('user_id');

        return $leaderboardEntries
            ->map(function ($user) use ($prizesByUser): array {
                $prizeStatuses = ($prizesByUser[$user->id] ?? collect())
                    ->map(fn (UserPrize $userPrize): string => "{$userPrize->prize?->title} ({$userPrize->status->value})")
                    ->implode(', ');

                return [
                    'rank' => (int) $user->getAttribute('rank'),
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'best_score' => $user->best_score,
                    'prize_status' => $prizeStatuses !== '' ? $prizeStatuses : 'No prizes assigned',
                ];
            })
            ->all();
    }
}
