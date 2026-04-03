<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skins', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code')->unique();
            $table->unsignedInteger('price');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skins');
    }
};
