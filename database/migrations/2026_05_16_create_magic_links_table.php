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
        Schema::create('magic_links', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token', 255)->unique();
            $table->string('name')->nullable();
            $table->string('whatsapp')->nullable();
            $table->json('quiz_data')->nullable(); // Store quiz report data
            $table->boolean('used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            $table->index('email');
            $table->index('token');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('magic_links');
    }
};
