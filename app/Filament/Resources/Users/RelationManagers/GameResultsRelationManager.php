<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Models\GameScore;
use App\Support\AdminPanelLabel;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GameResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'scores';

    protected static ?string $title = 'Игровые результаты';

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['gameSession', 'suspiciousEvent']))
            ->defaultSort('created_at', 'desc')
            ->recordClasses(fn (GameScore $record): array => match (true) {
                $record->hasTimestampInconsistency() => ['bg-danger-50'],
                ! $record->isServerDurationReliable() => ['bg-warning-50'],
                $record->hasPointsBearingCheatSignal() => ['bg-danger-50'],
                $record->hasTimingOnlySignals() => ['bg-warning-50'],
                default => [],
            })
            ->columns([
                TextColumn::make('id')
                    ->label('Score ID')
                    ->sortable(),
                TextColumn::make('gameSession.id')
                    ->label('Session ID')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy(
                        DB::table('game_sessions')
                            ->select('id')
                            ->whereColumn('game_sessions.token', 'game_scores.session_token')
                            ->limit(1),
                        $direction,
                    )),
                TextColumn::make('score')
                    ->label('Счет')
                    ->sortable(),
                TextColumn::make('coins_collected')
                    ->label('Монеты')
                    ->sortable(),
                TextColumn::make('gameSession.status')
                    ->label('Статус сессии')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state instanceof \BackedEnum ? (string) $state->value : (string) $state),
                TextColumn::make('gameSession.issued_at')
                    ->label('Issued')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('gameSession.submitted_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Score created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('runtime_server_duration')
                    ->label('Server duration (submitted)')
                    ->state(fn (GameScore $record): ?string => $record->runtimeStyleServerDurationSeconds() !== null ? $record->runtimeStyleServerDurationSeconds().'s' : null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw($this->runtimeServerDurationExpression().' '.$direction)),
                TextColumn::make('historical_server_duration')
                    ->label('Server duration (score created)')
                    ->state(fn (GameScore $record): ?string => $record->historicalStyleServerDurationSeconds() !== null ? $record->historicalStyleServerDurationSeconds().'s' : null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw($this->historicalServerDurationExpression().' '.$direction)),
                TextColumn::make('client_duration')
                    ->label('Client duration')
                    ->state(fn (GameScore $record): ?string => $record->clientDurationSeconds() !== null ? $record->clientDurationSeconds().'s' : null),
                TextColumn::make('duration_reliability')
                    ->label('Duration reliability')
                    ->badge()
                    ->color(fn (GameScore $record): string => $record->isServerDurationReliable() ? 'success' : 'warning')
                    ->state(fn (GameScore $record): string => AdminPanelLabel::durationReliability($record->durationReliabilityStatus()))
                    ->tooltip(fn (GameScore $record): string => sprintf(
                        'persisted=%s; runtime=%s',
                        $record->persistedDurationReliabilityStatus() ?? 'n/a',
                        $record->runtimeDurationReliabilityStatus(),
                    )),
                TextColumn::make('runtime_server_sps')
                    ->label('Server score/sec (submitted)')
                    ->state(fn (GameScore $record): ?string => $record->runtimeStyleServerScorePerSecond() !== null ? number_format($record->runtimeStyleServerScorePerSecond(), 2, '.', '') : null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw($this->runtimeServerScorePerSecondExpression().' '.$direction)),
                TextColumn::make('historical_server_sps')
                    ->label('Server score/sec (score created)')
                    ->state(fn (GameScore $record): ?string => $record->historicalStyleServerScorePerSecond() !== null ? number_format($record->historicalStyleServerScorePerSecond(), 2, '.', '') : null)
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw($this->historicalServerScorePerSecondExpression().' '.$direction)),
                TextColumn::make('client_sps')
                    ->label('Client score/sec')
                    ->state(fn (GameScore $record): ?string => $record->clientScorePerSecond() !== null ? number_format($record->clientScorePerSecond(), 2, '.', '') : null),
                TextColumn::make('cheat_status')
                    ->label('Cheat status')
                    ->badge()
                    ->color(fn (GameScore $record): string => match ($record->suspiciousStatus()) {
                        'critical' => 'danger',
                        'hard' => 'danger',
                        'soft' => 'warning',
                        'timing_only' => 'warning',
                        default => 'gray',
                    })
                    ->state(fn (GameScore $record): string => $record->suspiciousStatus())
                    ->tooltip(fn (GameScore $record): string => $record->suspiciousStatus()),
                TextColumn::make('timing_status')
                    ->label('Timing status')
                    ->badge()
                    ->color(fn (GameScore $record): string => $record->hasTimingAnomaly() ? 'warning' : 'gray')
                    ->state(fn (GameScore $record): string => match (true) {
                        $record->hasTimestampInconsistency() => 'inconsistent',
                        $record->hasTimingAnomaly() => 'Проблема с временем',
                        default => 'none',
                    })
                    ->tooltip(fn (GameScore $record): string => implode(', ', $record->suspiciousReasons())),
                TextColumn::make('suspicious_reasons')
                    ->label('Signals')
                    ->state(fn (GameScore $record): string => $record->translatedSuspiciousReasons() === [] ? 'Нет' : implode(', ', $record->translatedSuspiciousReasons()))
                    ->tooltip(fn (GameScore $record): string => $record->suspiciousReasons() === [] ? 'none' : implode(', ', $record->suspiciousReasons()))
                    ->wrap(),
                TextColumn::make('suspicious_points')
                    ->label('Points')
                    ->state(fn (GameScore $record): int => $record->suspiciousPoints())
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy(
                        DB::table('user_suspicious_events')
                            ->select('points')
                            ->whereColumn('user_suspicious_events.game_score_id', 'game_scores.id')
                            ->limit(1),
                        $direction,
                    )),
                TextColumn::make('device_id')
                    ->label('Device')
                    ->state(fn (GameScore $record): ?string => $record->sessionDeviceId())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('platform')
                    ->label('Platform')
                    ->state(fn (GameScore $record): ?string => $record->sessionPlatform()),
                TextColumn::make('app_version')
                    ->label('App version')
                    ->state(fn (GameScore $record): ?string => $record->sessionAppVersion())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('only_suspicious')
                    ->label('Only suspicious')
                    ->query(fn (Builder $query): Builder => $query->whereHas('suspiciousEvent')),
                Filter::make('only_timing_anomalies')
                    ->label('Only timing anomalies')
                    ->query(fn (Builder $query): Builder => $query->whereHas('suspiciousEvent', fn (Builder $eventQuery): Builder => $eventQuery->where(function (Builder $inner): Builder {
                        return $inner
                            ->where('reason', 'duration_mismatch')
                            ->orWhere('reason', 'unreliable_server_duration')
                            ->orWhere('signals', 'like', '%unreliable_server_duration%')
                            ->orWhere('signals', 'like', '%duration_mismatch%');
                    }))),
                Filter::make('only_unreliable_timing')
                    ->label('Only unreliable timing')
                    ->query(fn (Builder $query): Builder => $query->whereHas('suspiciousEvent', fn (Builder $eventQuery): Builder => $eventQuery->where(function (Builder $inner): Builder {
                        return $inner
                            ->where('reason', 'unreliable_server_duration')
                            ->orWhere('signals', 'like', '%unreliable_server_duration%');
                    }))),
                Filter::make('only_duration_mismatch')
                    ->label('Only duration mismatch')
                    ->query(fn (Builder $query): Builder => $query->whereHas('suspiciousEvent', fn (Builder $eventQuery): Builder => $eventQuery->where(function (Builder $inner): Builder {
                        return $inner
                            ->where('reason', 'duration_mismatch')
                            ->orWhere('signals', 'like', '%duration_mismatch%');
                    }))),
                Filter::make('only_cheat_signals')
                    ->label('Only cheat signals')
                    ->query(fn (Builder $query): Builder => $query->whereHas('suspiciousEvent', fn (Builder $eventQuery): Builder => $eventQuery->where(function (Builder $inner): Builder {
                        return $inner
                            ->whereNotIn('reason', ['duration_mismatch', 'unreliable_server_duration'])
                            ->orWhere('signals', 'like', '%adaptive_score_limit_exceeded%')
                            ->orWhere('signals', 'like', '%high_score_velocity%');
                    }))),
                Filter::make('only_high_velocity')
                    ->label('Only high velocity')
                    ->query(fn (Builder $query): Builder => $query->whereHas('suspiciousEvent', fn (Builder $eventQuery): Builder => $eventQuery->where(function (Builder $inner): Builder {
                        return $inner
                            ->where('reason', 'high_score_velocity')
                            ->orWhere('signals', 'like', '%high_score_velocity%');
                    }))),
                Filter::make('score_range')
                    ->label('Score range')
                    ->form([
                        TextInput::make('score_from')->numeric()->label('Score from'),
                        TextInput::make('score_to')->numeric()->label('Score to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(filled($data['score_from'] ?? null), fn (Builder $inner): Builder => $inner->where('score', '>=', (int) $data['score_from']))
                            ->when(filled($data['score_to'] ?? null), fn (Builder $inner): Builder => $inner->where('score', '<=', (int) $data['score_to']));
                    }),
                Filter::make('date_range')
                    ->label('Date range')
                    ->form([
                        DatePicker::make('created_from')->label('Created from'),
                        DatePicker::make('created_to')->label('Created to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(filled($data['created_from'] ?? null), fn (Builder $inner): Builder => $inner->whereDate('created_at', '>=', $data['created_from']))
                            ->when(filled($data['created_to'] ?? null), fn (Builder $inner): Builder => $inner->whereDate('created_at', '<=', $data['created_to']));
                    }),
                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'ios' => 'ios',
                        'android' => 'android',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $platform = Arr::get($data, 'value');

                        if (! filled($platform)) {
                            return $query;
                        }

                        return $query->whereHas('gameSession', function (Builder $sessionQuery) use ($platform): Builder {
                            return $sessionQuery->where(function (Builder $inner) use ($platform): Builder {
                                return $inner
                                    ->where('metadata->submission->platform', $platform)
                                    ->orWhere('metadata->platform', $platform);
                            });
                        });
                    }),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected function runtimeServerDurationExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => 'GREATEST(EXTRACT(EPOCH FROM ((SELECT submitted_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1) - (SELECT issued_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1))), 0)',
            'mysql' => 'GREATEST(TIMESTAMPDIFF(SECOND, (SELECT issued_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1), (SELECT submitted_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1)), 0)',
            default => "MAX(CAST(strftime('%s', (SELECT submitted_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1)) AS INTEGER) - CAST(strftime('%s', (SELECT issued_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1)) AS INTEGER), 0)",
        };
    }

    protected function historicalServerDurationExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'pgsql' => 'GREATEST(EXTRACT(EPOCH FROM (game_scores.created_at - (SELECT issued_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1))), 0)',
            'mysql' => 'GREATEST(TIMESTAMPDIFF(SECOND, (SELECT issued_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1), game_scores.created_at), 0)',
            default => "MAX(CAST(strftime('%s', game_scores.created_at) AS INTEGER) - CAST(strftime('%s', (SELECT issued_at FROM game_sessions WHERE game_sessions.token = game_scores.session_token LIMIT 1)) AS INTEGER), 0)",
        };
    }

    protected function runtimeServerScorePerSecondExpression(): string
    {
        $durationExpression = $this->runtimeServerDurationExpression();

        return "CASE WHEN {$durationExpression} > 0 THEN (game_scores.score * 1.0) / {$durationExpression} ELSE 0 END";
    }

    protected function historicalServerScorePerSecondExpression(): string
    {
        $durationExpression = $this->historicalServerDurationExpression();

        return "CASE WHEN {$durationExpression} > 0 THEN (game_scores.score * 1.0) / {$durationExpression} ELSE 0 END";
    }
}
