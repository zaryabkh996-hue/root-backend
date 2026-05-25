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
            // Add profile fields if they don't exist
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('whatsapp');
            }
            if (!Schema::hasColumn('users', 'bio_privacy')) {
                $table->enum('bio_privacy', ['public', 'community', 'private'])->default('public')->after('bio');
            }
            if (!Schema::hasColumn('users', 'travel_date')) {
                $table->string('travel_date')->nullable()->after('bio_privacy');
            }
            if (!Schema::hasColumn('users', 'travel_location')) {
                $table->string('travel_location')->nullable()->after('travel_date');
            }
            if (!Schema::hasColumn('users', 'diaspora_group')) {
                $table->string('diaspora_group')->nullable()->after('travel_location');
            }
            if (!Schema::hasColumn('users', 'learning_preference')) {
                $table->string('learning_preference')->nullable()->after('diaspora_group');
            }
            if (!Schema::hasColumn('users', 'profile_visibility')) {
                $table->enum('profile_visibility', ['public', 'community', 'private'])->default('public')->after('learning_preference');
            }
            if (!Schema::hasColumn('users', 'journey_photos_default')) {
                $table->enum('journey_photos_default', ['public', 'community', 'private'])->default('community')->after('profile_visibility');
            }
            if (!Schema::hasColumn('users', 'show_score_publicly')) {
                $table->boolean('show_score_publicly')->default(true)->after('journey_photos_default');
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
                'bio',
                'bio_privacy',
                'travel_date',
                'travel_location',
                'diaspora_group',
                'learning_preference',
                'profile_visibility',
                'journey_photos_default',
                'show_score_publicly',
            ]);
        });
    }
};
