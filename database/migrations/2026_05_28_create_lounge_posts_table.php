<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lounge_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('content');
            $table->enum('category', ['question', 'tip', 'discussion'])->default('question');
            $table->integer('likes_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('category');
        });

        Schema::create('lounge_post_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('lounge_posts')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
        });

        Schema::create('lounge_post_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('lounge_posts')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();

            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lounge_post_replies');
        Schema::dropIfExists('lounge_post_likes');
        Schema::dropIfExists('lounge_posts');
    }
};
