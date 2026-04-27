<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('has_suspicious_game_results')->default(false)->after('is_admin')->index();
            $table->timestamp('suspicious_game_results_flagged_at')->nullable()->after('has_suspicious_game_results');
            $table->string('suspicious_game_results_reason', 64)->nullable()->after('suspicious_game_results_flagged_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['has_suspicious_game_results']);
            $table->dropColumn([
                'has_suspicious_game_results',
                'suspicious_game_results_flagged_at',
                'suspicious_game_results_reason',
            ]);
        });
    }
};
