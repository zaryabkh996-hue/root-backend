<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add OAuth fields if they don't exist
            if (!Schema::hasColumn('users', 'auth0_id')) {
                $table->string('auth0_id')->nullable()->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'picture')) {
                $table->string('picture')->nullable()->after('auth0_id');
            }
            if (!Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable()->default('password')->after('picture');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['auth0_id', 'picture', 'provider']);
        });
    }
};
