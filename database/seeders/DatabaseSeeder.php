<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'password' => Hash::make('password'),
                'best_score' => 0,
                'coins' => 0,
                'active_skin_id' => null,
                'last_rank_cached' => null,
                'is_admin' => false,
            ],
        );
    }
}
