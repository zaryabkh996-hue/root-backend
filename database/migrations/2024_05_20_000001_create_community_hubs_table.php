<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('adinkra'); // e.g., "Akoma"
            $table->string('emoji'); // e.g., "💕"
            $table->text('description');
            $table->enum('access_level', ['free', 'community', 'preparation', 'locked'])->default('community');
            $table->string('access_label')->default('Read free · post Community+');
            $table->string('border_color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index('access_level');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_hubs');
    }
};
