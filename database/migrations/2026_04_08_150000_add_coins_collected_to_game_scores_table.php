<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_scores', function (Blueprint $table): void {
            $table->unsignedInteger('coins_collected')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('game_scores', function (Blueprint $table): void {
            $table->dropColumn('coins_collected');
        });
    }
};
