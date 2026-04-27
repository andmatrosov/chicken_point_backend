<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('kind')->default('post_prize_assignment')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('captured_at');
            $table->string('source_hash', 64)->nullable();
            $table->json('payload');
            $table->foreignId('frozen_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('frozen_at');
            $table->foreignId('cleared_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_snapshots');
    }
};
