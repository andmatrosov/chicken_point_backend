<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score')->index();
            $table->string('session_token');
            $table->boolean('is_processed')->default(false);
            $table->timestamps();

            $table->index('session_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_scores');
    }
};
