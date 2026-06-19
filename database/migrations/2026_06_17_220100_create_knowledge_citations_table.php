<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Knowledge Bank citations table — tracks every time Amen AI cites
     * a custodian's knowledge fragment. Drives the $0.35/citation payout
     * system (monthly via M-Pesa / Paystack / bank transfer).
     */
    public function up(): void
    {
        Schema::create('knowledge_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_id')->constrained('knowledge_contributions')->onDelete('cascade');
            $table->foreignId('custodian_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');

            $table->string('conversation_id', 100)->index(); // Chat session UUID
            $table->decimal('pinecone_score', 4, 3);          // Cosine similarity score (e.g. 0.842)
            $table->decimal('payout_amount', 8, 2)->default(0.35);

            $table->enum('payout_status', [
                'pending',  // Awaiting monthly settlement
                'paid',     // Paid out via M-Pesa / Paystack / bank
                'failed',   // Payment failed — retry needed
            ])->default('pending')->index();

            $table->timestamp('cited_at');
            $table->timestamps();

            // Indexes for reporting
            $table->index(['custodian_id', 'payout_status']);
            $table->index(['cited_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_citations');
    }
};
