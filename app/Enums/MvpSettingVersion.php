<?php

namespace App\Enums;

enum MvpSettingVersion: string
{
    case MAIN = 'main';
    case BRAZIL = 'brazil';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $version): string => $version->value,
            self::cases(),
        );
    }
}
