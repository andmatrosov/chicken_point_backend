<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mvp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('mvp_link', 2048)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        $timestamp = now();

        DB::table('mvp_settings')->insert([
            [
                'version' => 'main',
                'mvp_link' => null,
                'is_active' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'version' => 'brazil',
                'mvp_link' => null,
                'is_active' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('mvp_settings');
    }
};
