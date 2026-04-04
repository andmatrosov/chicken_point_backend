<?php

namespace App\Support;

class CountryFlagHelper
{
    public static function fromCode(?string $code): ?string
    {
        if (! is_string($code)) {
            return null;
        }

        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return null;
        }

        $flag = '';

        foreach (str_split($code) as $letter) {
            $flag .= mb_chr(0x1F1E6 + ord($letter) - ord('A'), 'UTF-8');
        }

        return $flag !== '' ? $flag : null;
    }
}
