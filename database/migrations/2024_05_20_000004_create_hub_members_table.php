<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('community_hubs')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['hub_id', 'user_id']);
            $table->index('hub_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_members');
    }
};
