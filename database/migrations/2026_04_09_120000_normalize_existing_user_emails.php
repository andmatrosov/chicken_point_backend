<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $duplicateEmails = DB::table('users')
            ->selectRaw('LOWER(TRIM(email)) as normalized_email')
            ->groupByRaw('LOWER(TRIM(email))')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('normalized_email');

        if ($duplicateEmails->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot normalize user emails because case-insensitive duplicates already exist: '
                .$duplicateEmails->implode(', '),
            );
        }

        DB::table('users')
            ->select(['id', 'email'])
            ->orderBy('id')
            ->chunkById(100, function ($users): void {
                foreach ($users as $user) {
                    $normalizedEmail = mb_strtolower(trim((string) $user->email));

                    if ($normalizedEmail === $user->email) {
                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['email' => $normalizedEmail]);
                }
            });
    }

    public function down(): void
    {
    }
};
