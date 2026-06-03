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
        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_user_id')->constrained('users')->onDelete('cascade');
            $table->string('item_type'); // 'thread' or 'reply'
            $table->unsignedBigInteger('item_id');
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'dismissed', 'warned', 'banned'])->default('pending');
            $table->text('warning_message')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('reporter_id');
            $table->index('reported_user_id');
            $table->index('status');
            $table->unique(['reporter_id', 'item_type', 'item_id'], 'reporter_item_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_reports');
    }
};
