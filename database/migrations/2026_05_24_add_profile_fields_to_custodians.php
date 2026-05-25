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
            // Profile fields for custodians
            if (!Schema::hasColumn('users', 'short_bio')) {
                $table->text('short_bio')->nullable()->comment('Personal quote or tagline');
            }
            if (!Schema::hasColumn('users', 'about')) {
                $table->longText('about')->nullable()->comment('Detailed about/biography section');
            }
            if (!Schema::hasColumn('users', 'languages')) {
                $table->json('languages')->nullable()->comment('Array of languages spoken');
            }
            if (!Schema::hasColumn('users', 'services')) {
                $table->json('services')->nullable()->comment('Array of services with pricing');
            }
            if (!Schema::hasColumn('users', 'testimonials')) {
                $table->json('testimonials')->nullable()->comment('Array of testimonials');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'short_bio',
                'about',
                'languages',
                'services',
                'testimonials',
            ]);
        });
    }
};
