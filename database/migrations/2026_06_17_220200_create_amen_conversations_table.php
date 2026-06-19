<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amen AI conversations table — logs all messages for:
     * - Conversation continuity (chat history)
     * - Future fine-tuning dataset (target: 5,000+ quality examples)
     * - Admin review and quality assurance
     *
     * Stored with explicit user consent (GDPR/POPIA compliant).
     */
    public function up(): void
    {
        Schema::create('amen_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('conversation_id', 100)->index(); // Session UUID — groups messages

            $table->enum('role', ['user', 'assistant']); // Who sent the message
            $table->text('content');                       // Message text

            // Knowledge Bank metadata (if fragment was used in this response)
            $table->boolean('fragment_used')->default(false);
            $table->foreignId('contribution_id')
                ->nullable()
                ->constrained('knowledge_contributions')
                ->onDelete('set null');

            // AI provider metadata
            $table->string('model_used', 50)->nullable();  // gpt-4o, claude-sonnet, etc.
            $table->unsignedInteger('tokens_used')->nullable();

            $table->timestamps();

            // Indexes for chat history retrieval and analytics
            $table->index(['user_id', 'conversation_id']);
            $table->index(['conversation_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amen_conversations');
    }
};
