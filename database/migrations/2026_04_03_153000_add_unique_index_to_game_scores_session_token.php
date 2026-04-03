<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_scores', function (Blueprint $table): void {
            $table->dropIndex(['session_token']);
            $table->unique('session_token');
        });
    }

    public function down(): void
    {
        Schema::table('game_scores', function (Blueprint $table): void {
            $table->dropUnique(['session_token']);
            $table->index('session_token');
        });
    }
};
