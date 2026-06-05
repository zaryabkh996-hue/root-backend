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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('auth0_id')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->default('customer');
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('active');
            $table->string('whatsapp')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->json('quiz_data')->nullable();
            $table->string('picture')->nullable();
            $table->string('provider')->nullable()->default('password');

            // Custodian fields
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->integer('years_experience')->nullable();
            $table->string('specialty')->nullable();
            $table->string('avatar_initials')->nullable();
            $table->string('avatar_class')->nullable();
            $table->string('gradient_bg')->nullable();
            $table->string('availability')->default('Available')->nullable();
            $table->string('availability_text')->nullable();
            $table->string('share_text')->nullable();
            $table->longText('description')->nullable();
            $table->json('tags')->nullable();
            $table->decimal('price_from', 10, 2)->default(0.00)->nullable();
            $table->string('certification')->nullable();
            $table->string('coc_status')->nullable();
            $table->decimal('review_avg', 3, 2)->default(5.00)->nullable();
            $table->integer('sessions_count')->default(0);
            $table->boolean('verified')->default(false);
            $table->boolean('top_custodian')->default(false);

            // Custodian Profile Details
            $table->text('short_bio')->nullable();
            $table->longText('about')->nullable();
            $table->json('languages')->nullable();
            $table->json('services')->nullable();
            $table->json('testimonials')->nullable();

            // Profile fields for all users
            $table->text('bio')->nullable();
            $table->enum('bio_privacy', ['public', 'community', 'private'])->default('public');
            $table->string('travel_date')->nullable();
            $table->string('travel_location')->nullable();
            $table->string('diaspora_group')->nullable();
            $table->string('learning_preference')->nullable();
            $table->enum('profile_visibility', ['public', 'community', 'private'])->default('public');
            $table->enum('journey_photos_default', ['public', 'community', 'private'])->default('community');
            $table->boolean('show_score_publicly')->default(true);
            $table->json('notification_preferences')->nullable();

            $table->rememberToken();
            $table->timestamps();

            // Indexes for performance
            $table->index('role');
            $table->index('status');
            $table->index('country');
            $table->index('specialty');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
