<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verify_code', 6)->nullable()->after('email_verified_at');
            $table->timestamp('verify_code_expires_at')->nullable()->after('verify_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verify_code', 'verify_code_expires_at']);
        });
    }
};
