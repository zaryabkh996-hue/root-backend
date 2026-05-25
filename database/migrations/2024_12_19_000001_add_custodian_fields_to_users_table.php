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
            // Custodian-specific fields
            if (!Schema::hasColumn('users', 'location')) {
                $table->string('location')->nullable();
            }
            if (!Schema::hasColumn('users', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('users', 'years_experience')) {
                $table->integer('years_experience')->default(0);
            }
            if (!Schema::hasColumn('users', 'specialty')) {
                $table->string('specialty')->nullable();
            }
            if (!Schema::hasColumn('users', 'avatar_class')) {
                $table->string('avatar_class')->nullable();
            }
            if (!Schema::hasColumn('users', 'gradient_bg')) {
                $table->string('gradient_bg')->nullable();
            }
            if (!Schema::hasColumn('users', 'availability')) {
                $table->string('availability')->default('Available')->nullable();
            }
            if (!Schema::hasColumn('users', 'description')) {
                $table->longText('description')->nullable();
            }
            if (!Schema::hasColumn('users', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (!Schema::hasColumn('users', 'price_from')) {
                $table->decimal('price_from', 10, 2)->default(0);
            }
            if (!Schema::hasColumn('users', 'certification')) {
                $table->string('certification')->nullable();
            }
            if (!Schema::hasColumn('users', 'coc_status')) {
                $table->string('coc_status')->nullable();
            }
            if (!Schema::hasColumn('users', 'review_avg')) {
                $table->decimal('review_avg', 3, 2)->default(5.00);
            }
            if (!Schema::hasColumn('users', 'sessions_count')) {
                $table->integer('sessions_count')->default(0);
            }
            if (!Schema::hasColumn('users', 'whatsapp')) {
                $table->string('whatsapp')->nullable();
            }
            if (!Schema::hasColumn('users', 'quiz_data')) {
                $table->json('quiz_data')->nullable();
            }
            if (!Schema::hasColumn('users', 'auth0_id')) {
                $table->string('auth0_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'picture')) {
                $table->string('picture')->nullable();
            }
            if (!Schema::hasColumn('users', 'provider')) {
                $table->string('provider')->nullable();
            }

            // Indexes for performance
            $table->index('role');
            $table->index('country');
            $table->index('specialty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['country']);
            $table->dropIndex(['specialty']);

            $table->dropColumn([
                'location',
                'country',
                'years_experience',
                'specialty',
                'avatar_class',
                'gradient_bg',
                'availability',
                'description',
                'tags',
                'price_from',
                'certification',
                'coc_status',
                'review_avg',
                'sessions_count',
                'auth0_id',
                'picture',
                'provider',
            ]);
        });
    }
};
