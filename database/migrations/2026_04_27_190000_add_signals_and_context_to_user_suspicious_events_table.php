<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_suspicious_events', function (Blueprint $table): void {
            $table->json('signals')->nullable()->after('points');
            $table->json('context')->nullable()->after('signals');
        });
    }

    public function down(): void
    {
        Schema::table('user_suspicious_events', function (Blueprint $table): void {
            $table->dropColumn(['signals', 'context']);
        });
    }
};
