<?php

namespace App\Support;

use App\Enums\GameSessionStatus;
use App\Enums\MvpSettingVersion;
use App\Enums\UserPrizeStatus;

class AdminPanelLabel
{
    public static function gameSessionStatus(GameSessionStatus|string|null $state): ?string
    {
        return match (true) {
            $state instanceof GameSessionStatus => self::gameSessionStatus($state->value),
            $state === GameSessionStatus::ACTIVE->value => 'Активна',
            $state === GameSessionStatus::SUBMITTED->value => 'Отправлена',
            $state === GameSessionStatus::EXPIRED->value => 'Истекла',
            $state === GameSessionStatus::CANCELED->value => 'Отменена',
            blank($state) => null,
            default => (string) $state,
        };
    }

    public static function leaderboardPrizeStatusSummary(string $status): string
    {
        return match ($status) {
            'Issued' => 'Выдан',
            'Pending' => 'В ожидании',
            'Canceled' => 'Отменен',
            'Mixed' => 'Смешанный',
            'Unassigned' => 'Не назначен',
            default => $status,
        };
    }

    public static function mvpVersion(MvpSettingVersion|string|null $state): ?string
    {
        return match (true) {
            $state instanceof MvpSettingVersion => self::mvpVersion($state->value),
            $state === MvpSettingVersion::MAIN->value => 'Main',
            $state === MvpSettingVersion::BRAZIL->value => 'Brazil',
            blank($state) => null,
            default => ucfirst((string) $state),
        };
    }

    public static function previewStatus(string $status): string
    {
        return match ($status) {
            'assigned' => 'Назначен',
            'ready' => 'Готово',
            'warning' => 'Требует внимания',
            'skipped' => 'Пропущен',
            default => ucfirst($status),
        };
    }

    public static function userPrizeStatus(UserPrizeStatus|string|null $state): ?string
    {
        return match (true) {
            $state instanceof UserPrizeStatus => self::userPrizeStatus($state->value),
            $state === UserPrizeStatus::PENDING->value => 'В ожидании',
            $state === UserPrizeStatus::ISSUED->value => 'Выдан',
            $state === UserPrizeStatus::CANCELED->value => 'Отменен',
            blank($state) => null,
            default => (string) $state,
        };
    }
}
