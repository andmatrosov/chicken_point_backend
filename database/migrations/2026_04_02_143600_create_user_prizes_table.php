<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('rank_at_assignment')->nullable();
            $table->boolean('assigned_manually')->default(false);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->string('status', 32)->index();
            $table->timestamps();

            $table->index(['user_id', 'prize_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_prizes');
    }
};
