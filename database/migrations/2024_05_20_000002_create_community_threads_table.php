<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('community_hubs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->longText('content');
            $table->string('location')->nullable();
            $table->string('user_stage')->nullable(); // e.g., "Stage 2"
            $table->string('user_tier')->nullable(); // e.g., "Heritage Seeker"
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();

            $table->index('hub_id');
            $table->index('user_id');
            $table->index(['hub_id', 'is_active']);
            $table->index('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_threads');
    }
};
