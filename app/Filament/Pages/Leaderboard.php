<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\LeaderboardService;
use App\Services\PrizeAutoAssignmentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class Leaderboard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 70;

    protected ?string $heading = 'Leaderboard';

    protected string $view = 'filament.pages.leaderboard';

    public ?array $previewResult = null;

    public ?array $assignmentResult = null;

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    public function getSubheading(): ?string
    {
        return 'Current top players by best score, with admin-visible emails and prize assignment state.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewPrizeAssignments')
                ->label('Preview Prize Assignments')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->action(function (PrizeAutoAssignmentService $prizeAutoAssignmentService): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        $this->previewResult = $prizeAutoAssignmentService->previewCurrentLeaderboardAssignments($admin);

                        Notification::make()
                            ->success()
                            ->title('Preview generated')
                            ->body("{$this->previewResult['ready_count']} assignments are ready and {$this->previewResult['skipped_count']} entries need attention.")
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
                ->icon(Heroicon::OutlinedGift)
                ->color('primary')
                ->requiresConfirmation()
                ->modalDescription('This uses the current leaderboard snapshot and respects configured prize stock.')
                ->action(function (PrizeAutoAssignmentService $prizeAutoAssignmentService): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        $this->assignmentResult = $prizeAutoAssignmentService->assignCurrentLeaderboardPrizes($admin);
                        $this->previewResult = null;
                        $this->resetTable();

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

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, ?string $sortColumn, ?string $sortDirection): Collection => $this->getTableRecordsCollection(
                $search,
                $sortColumn,
                $sortDirection,
            ))
            ->paginated(false)
            ->searchable()
            ->striped()
            ->defaultSort('rank')
            ->description('The leaderboard is derived from users.best_score. Full emails are visible here for admin review.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->emptyStateHeading('No leaderboard entries yet')
            ->emptyStateDescription('Once players submit scores, the current top list will appear here.')
            ->recordActions([
                Action::make('viewUser')
                    ->label('View user')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (array $record): string => UserResource::getUrl('view', ['record' => $record['user_id']])),
            ])
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->badge()
                    ->alignment(Alignment::Center)
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->color(fn (int $state): string => match (true) {
                        $state === 1 => 'warning',
                        $state === 2 => 'gray',
                        $state === 3 => 'info',
                        default => 'primary',
                    })
                    ->icon(fn (int $state) => $state <= 3 ? Heroicon::OutlinedTrophy : null),
                TextColumn::make('user_id')
                    ->label('User ID')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->sortable()
                    ->alignment(Alignment::Center),
                TextColumn::make('email')
                    ->label('Player Email')
                    ->weight(FontWeight::Medium)
                    ->copyable()
                    ->sortable()
                    ->description(fn (array $record): ?string => $record['prize_count'] > 0 ? "{$record['prize_count']} assignment(s)" : null),
                TextColumn::make('best_score')
                    ->label('Best Score')
                    ->numeric()
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable(),
                TextColumn::make('prize_status_summary')
                    ->label('Assignment Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => $this->getPrizeStatusColor($state))
                    ->icon(fn (string $state) => match ($state) {
                        'Issued' => Heroicon::OutlinedCheckBadge,
                        'Pending' => Heroicon::OutlinedClock,
                        'Canceled' => Heroicon::OutlinedXCircle,
                        'Mixed' => Heroicon::OutlinedExclamationCircle,
                        default => Heroicon::OutlinedMinusCircle,
                    }),
                TextColumn::make('prize_assignments')
                    ->label('Prizes')
                    ->badge()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('No prizes assigned')
                    ->color(function (string $state): string {
                        $normalized = Str::lower($state);

                        return match (true) {
                            Str::contains($normalized, 'issued') => 'success',
                            Str::contains($normalized, 'pending') => 'warning',
                            Str::contains($normalized, 'canceled') => 'danger',
                            default => 'gray',
                        };
                    }),
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRows(): array
    {
        return $this->getLeaderboardRows()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function getLeaderboardRows(): Collection
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
                /** @var Collection<int, UserPrize> $userPrizes */
                $userPrizes = $prizesByUser[$user->id] ?? collect();

                $prizeAssignments = $userPrizes
                    ->map(fn (UserPrize $userPrize): string => sprintf(
                        '%s - %s',
                        $userPrize->prize?->title ?? 'Unknown prize',
                        Str::headline($userPrize->status->value),
                    ))
                    ->values()
                    ->all();

                $prizeStatuses = $userPrizes
                    ->map(fn (UserPrize $userPrize): string => Str::headline($userPrize->status->value))
                    ->unique()
                    ->values();

                return [
                    'rank' => (int) $user->getAttribute('rank'),
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'best_score' => (int) $user->best_score,
                    'prize_status_summary' => $this->summarizePrizeStatuses($prizeStatuses),
                    'prize_assignments' => $prizeAssignments,
                    'prize_count' => count($prizeAssignments),
                ];
            })
            ->values();
    }

    /**
     * @return Collection<int|string, array<string, mixed>>
     */
    protected function getTableRecordsCollection(
        ?string $search,
        ?string $sortColumn,
        ?string $sortDirection,
    ): Collection {
        $records = $this->getLeaderboardRows();

        if (filled($search)) {
            $search = Str::lower($search);

            $records = $records->filter(function (array $record) use ($search): bool {
                return Str::contains(Str::lower($record['email']), $search)
                    || Str::contains((string) $record['user_id'], $search)
                    || Str::contains((string) $record['rank'], $search)
                    || Str::contains((string) $record['best_score'], $search)
                    || Str::contains(Str::lower($record['prize_status_summary']), $search)
                    || Str::contains(Str::lower(implode(' ', $record['prize_assignments'])), $search);
            });
        }

        $sortColumn ??= 'rank';
        $sortDirection ??= 'asc';

        $records = $records->sortBy(
            fn (array $record): mixed => match ($sortColumn) {
                'best_score' => $record['best_score'],
                'email' => Str::lower($record['email']),
                'user_id' => $record['user_id'],
                'prize_status_summary' => $record['prize_status_summary'],
                default => $record['rank'],
            },
            SORT_NATURAL,
            $sortDirection === 'desc',
        );

        return $records->keyBy(fn (array $record): int => $record['user_id']);
    }

    /**
     * @param  Collection<int, string>  $statuses
     */
    protected function summarizePrizeStatuses(Collection $statuses): string
    {
        if ($statuses->isEmpty()) {
            return 'Unassigned';
        }

        if ($statuses->count() === 1) {
            return $statuses->first();
        }

        return 'Mixed';
    }

    public function getPrizeStatusColor(string $status): string
    {
        return match ($status) {
            'Issued' => 'success',
            'Pending' => 'warning',
            'Canceled' => 'danger',
            'Mixed' => 'info',
            default => 'gray',
        };
    }

    public function getPreviewEntryStatusColor(string $status): string
    {
        return match ($status) {
            'assigned', 'ready' => 'success',
            'warning' => 'warning',
            'skipped' => 'gray',
            default => 'gray',
        };
    }
}
