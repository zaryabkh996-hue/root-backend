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
            // Add these columns if they don't exist
            if (!Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'quiz_data')) {
                $table->json('quiz_data')->nullable()->after('whatsapp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'quiz_data']);
        });
    }
};
