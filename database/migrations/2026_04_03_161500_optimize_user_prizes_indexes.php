<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_prizes', function (Blueprint $table): void {
            $table->index(['prize_id', 'status']);
            $table->index(['user_id', 'prize_id', 'status']);
            $table->index(['user_id', 'status', 'assigned_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_prizes', function (Blueprint $table): void {
            $table->dropIndex(['prize_id', 'status']);
            $table->dropIndex(['user_id', 'prize_id', 'status']);
            $table->dropIndex(['user_id', 'status', 'assigned_at', 'id']);
        });
    }
};
