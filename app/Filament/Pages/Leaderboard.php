<?php

namespace App\Filament\Pages;

use App\Enums\UserPrizeStatus;
use App\Exceptions\BusinessException;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Models\UserPrize;
use App\Services\LeaderboardService;
use App\Services\PrizeAutoAssignmentService;
use App\Support\AdminPanelLabel;
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

    protected static string|\UnitEnum|null $navigationGroup = 'Операции';

    protected static ?int $navigationSort = 70;

    protected static ?string $navigationLabel = 'Таблица лидеров';

    protected ?string $heading = 'Таблица лидеров';

    protected string $view = 'filament.pages.leaderboard';

    public ?array $previewResult = null;

    public ?array $assignmentResult = null;

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    public function getSubheading(): ?string
    {
        return 'Текущие лучшие игроки по максимальному счету с полными email и состоянием назначенных призов.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('previewPrizeAssignments')
                ->label('Предпросмотр назначений')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->action(function (PrizeAutoAssignmentService $prizeAutoAssignmentService): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        $this->previewResult = $prizeAutoAssignmentService->previewCurrentLeaderboardAssignments($admin);

                        Notification::make()
                            ->success()
                            ->title('Предпросмотр сформирован')
                            ->body("Готово к назначению: {$this->previewResult['ready_count']}, требуют внимания: {$this->previewResult['skipped_count']}. Снимок готов к подтверждению.")
                            ->send();
                    } catch (AuthorizationException|Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Не удалось сформировать предпросмотр')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
            Action::make('autoAssignPrizes')
                ->label('Назначить призы автоматически')
                ->icon(Heroicon::OutlinedGift)
                ->color('primary')
                ->disabled(fn (): bool => ! $this->hasPreviewSnapshot())
                ->requiresConfirmation()
                ->modalDescription('Будет подтвержден последний снимок предпросмотра с учетом текущей доступности призов.')
                ->action(function (PrizeAutoAssignmentService $prizeAutoAssignmentService): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    if (! $this->hasPreviewSnapshot()) {
                        Notification::make()
                            ->danger()
                            ->title('Нужен предпросмотр')
                            ->body('Сначала сформируйте свежий предпросмотр, затем подтверждайте назначения.')
                            ->send();

                        return;
                    }

                    try {
                        $this->assignmentResult = $prizeAutoAssignmentService->assignPreviewedLeaderboardPrizes(
                            $admin,
                            (array) ($this->previewResult['snapshot'] ?? []),
                        );
                        $this->previewResult = null;
                        $this->resetTable();

                        Notification::make()
                            ->success()
                            ->title('Назначение призов завершено')
                            ->body("Назначено: {$this->assignmentResult['assigned_count']}, пропущено: {$this->assignmentResult['skipped_count']}.")
                            ->send();
                    } catch (BusinessException $exception) {
                        $this->previewResult = null;

                        Notification::make()
                            ->danger()
                            ->title('Не удалось назначить призы')
                            ->body($exception->getMessage())
                            ->send();
                    } catch (AuthorizationException|Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Не удалось назначить призы')
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
            ->description('Таблица лидеров строится по `users.best_score`. Полные email видны только администраторам.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleStack)
            ->emptyStateHeading('Записей в таблице лидеров пока нет')
            ->emptyStateDescription('После первых отправленных результатов здесь появится актуальный топ игроков.')
            ->recordActions([
                Action::make('viewUser')
                    ->label('Открыть участника')
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (array $record): string => UserResource::getUrl('view', ['record' => $record['user_id']])),
            ])
            ->columns([
                TextColumn::make('rank')
                    ->label('Ранг')
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
                    ->label('ID участника')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->sortable()
                    ->alignment(Alignment::Center),
                TextColumn::make('email')
                    ->label('Email игрока')
                    ->weight(FontWeight::Medium)
                    ->copyable()
                    ->sortable()
                    ->description(fn (array $record): ?string => $record['prize_count'] > 0 ? "Назначений: {$record['prize_count']}" : null),
                TextColumn::make('best_score')
                    ->label('Лучший счет')
                    ->numeric()
                    ->fontFamily(FontFamily::Mono)
                    ->alignment(Alignment::End)
                    ->sortable(),
                TextColumn::make('prize_status_summary')
                    ->label('Статус назначения')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => AdminPanelLabel::leaderboardPrizeStatusSummary($state))
                    ->color(fn (string $state): string => $this->getPrizeStatusColor($state))
                    ->icon(fn (string $state) => match ($state) {
                        'Issued' => Heroicon::OutlinedCheckBadge,
                        'Pending' => Heroicon::OutlinedClock,
                        'Canceled' => Heroicon::OutlinedXCircle,
                        'Mixed' => Heroicon::OutlinedExclamationCircle,
                        default => Heroicon::OutlinedMinusCircle,
                    }),
                TextColumn::make('prize_assignments')
                    ->label('Призы')
                    ->badge()
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->placeholder('Призы не назначены')
                    ->color(function (string $state): string {
                        $normalized = Str::lower($state);

                        return match (true) {
                            Str::contains($normalized, 'выдан') => 'success',
                            Str::contains($normalized, 'ожидан') => 'warning',
                            Str::contains($normalized, 'отмен') => 'danger',
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
                        $userPrize->prize?->title ?? 'Неизвестный приз',
                        AdminPanelLabel::userPrizeStatus($userPrize->status),
                    ))
                    ->values()
                    ->all();

                $prizeStatuses = $userPrizes
                    ->map(fn (UserPrize $userPrize): string => match ($userPrize->status) {
                        UserPrizeStatus::ISSUED => 'Issued',
                        UserPrizeStatus::PENDING => 'Pending',
                        UserPrizeStatus::CANCELED => 'Canceled',
                    })
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

    protected function hasPreviewSnapshot(): bool
    {
        return is_array($this->previewResult['snapshot'] ?? null)
            && is_array($this->previewResult['snapshot']['entries'] ?? null);
    }
}
