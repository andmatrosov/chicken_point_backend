<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('registration_ip', 45)->nullable()->after('email');
            $table->string('country_code', 5)->nullable()->after('registration_ip');
            $table->string('country_name')->nullable()->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'registration_ip',
                'country_code',
                'country_name',
            ]);
        });
    }
};
