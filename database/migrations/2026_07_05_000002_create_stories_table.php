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
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('sanity_id')->nullable()->index();
            $table->string('title');
            $table->text('body');
            $table->string('author');
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            $table->string('status')->default('pending')->index(); // pending, approved, revision
            $table->text('revision_note')->nullable();
            $table->foreignId('community_hub_id')->nullable()->constrained('community_hubs')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stories');
    }
};
