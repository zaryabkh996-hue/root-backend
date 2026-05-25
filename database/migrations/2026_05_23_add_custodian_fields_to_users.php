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
                $table->integer('years_experience')->nullable();
            }
            if (!Schema::hasColumn('users', 'specialty')) {
                $table->string('specialty')->nullable();
            }
            if (!Schema::hasColumn('users', 'description')) {
                $table->text('description')->nullable();
            }
            if (!Schema::hasColumn('users', 'availability')) {
                $table->string('availability')->default('Available');
            }
            if (!Schema::hasColumn('users', 'certification')) {
                $table->string('certification')->nullable();
            }
            if (!Schema::hasColumn('users', 'coc_status')) {
                $table->string('coc_status')->nullable();
            }
            if (!Schema::hasColumn('users', 'price_from')) {
                $table->decimal('price_from', 8, 2)->nullable();
            }
            if (!Schema::hasColumn('users', 'review_avg')) {
                $table->float('review_avg')->nullable();
            }
            if (!Schema::hasColumn('users', 'sessions_count')) {
                $table->integer('sessions_count')->default(0);
            }
            if (!Schema::hasColumn('users', 'tags')) {
                $table->json('tags')->nullable();
            }
            if (!Schema::hasColumn('users', 'avatar_initials')) {
                $table->string('avatar_initials')->nullable();
            }
            if (!Schema::hasColumn('users', 'avatar_class')) {
                $table->string('avatar_class')->nullable();
            }
            if (!Schema::hasColumn('users', 'gradient_bg')) {
                $table->string('gradient_bg')->nullable();
            }
            if (!Schema::hasColumn('users', 'verified')) {
                $table->boolean('verified')->default(false);
            }
            if (!Schema::hasColumn('users', 'top_custodian')) {
                $table->boolean('top_custodian')->default(false);
            }
            if (!Schema::hasColumn('users', 'availability_text')) {
                $table->string('availability_text')->nullable();
            }
            if (!Schema::hasColumn('users', 'share_text')) {
                $table->string('share_text')->nullable();
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
                'location',
                'country',
                'years_experience',
                'specialty',
                'description',
                'availability',
                'certification',
                'coc_status',
                'price_from',
                'review_avg',
                'sessions_count',
                'tags',
                'avatar_initials',
                'avatar_class',
                'gradient_bg',
                'verified',
                'top_custodian',
                'availability_text',
                'share_text',
            ]);
        });
    }
};
