<?php

namespace App\Services;

use App\Models\Prize;
use Illuminate\Validation\ValidationException;

class PrizeRangeValidationService
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function validateForUpsert(array $data, ?Prize $ignorePrize = null): void
    {
        $from = $this->normalizeInteger($data['default_rank_from'] ?? null);
        $to = $this->normalizeInteger($data['default_rank_to'] ?? null);
        $isActive = (bool) ($data['is_active'] ?? false);
        $errors = [];

        if (($from === null) xor ($to === null)) {
            $errors['default_rank_from'] = ['Both rank bounds must be provided together.'];
            $errors['default_rank_to'] = ['Both rank bounds must be provided together.'];
        }

        if ($from !== null && $to !== null && $from > $to) {
            $errors['default_rank_from'] = ['The start rank must be less than or equal to the end rank.'];
            $errors['default_rank_to'] = ['The end rank must be greater than or equal to the start rank.'];
        }

        if ($errors === [] && $isActive && $from !== null && $to !== null) {
            $overlappingPrize = Prize::query()
                ->where('is_active', true)
                ->whereNotNull('default_rank_from')
                ->whereNotNull('default_rank_to')
                ->when(
                    $ignorePrize !== null,
                    fn ($query) => $query->whereKeyNot($ignorePrize->id),
                )
                ->where('default_rank_from', '<=', $to)
                ->where('default_rank_to', '>=', $from)
                ->orderBy('default_rank_from')
                ->orderBy('id')
                ->first();

            if ($overlappingPrize !== null) {
                $message = sprintf(
                    'This active rank range overlaps with prize "%s" (#%d).',
                    $overlappingPrize->title,
                    $overlappingPrize->id,
                );

                $errors['default_rank_from'] = [$message];
                $errors['default_rank_to'] = [$message];
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    protected function normalizeInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
