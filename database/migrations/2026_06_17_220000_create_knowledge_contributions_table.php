<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Knowledge Bank contributions table — stores every custodian submission
     * through the 4-step Contribute flow. Tracks the full lifecycle from
     * submission → review → approval → Pinecone embedding.
     */
    public function up(): void
    {
        Schema::create('knowledge_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Submission content
            $table->string('title', 255);
            $table->text('description');
            $table->string('category', 50)->index(); // ceremony, language, food, dress, sites, music
            $table->string('ethnic_group', 100);      // Akan (Asante), Ewe, Ga-Adangbe, etc.
            $table->string('region', 100);             // Ashanti Region, Ghana
            $table->string('authority_role', 255);     // e.g. "Master kente weaver · Bonwire · 22 yrs"

            // Media (optional — audio/video auto-transcribed by Whisper)
            $table->string('media_path', 500)->nullable();
            $table->string('media_type', 20)->nullable(); // video, audio, image
            $table->text('transcription')->nullable();     // Whisper transcription output

            // Legal consent (Prior Informed Consent — Kenya TK Act 2016)
            $table->boolean('consent_signed')->default(false);
            $table->string('consent_signature', 255)->nullable();
            $table->string('consent_pdf_path', 500)->nullable();

            // Review & approval pipeline
            $table->enum('status', [
                'submitted',     // Just submitted by custodian
                'under_review',  // Being reviewed by Knowledge Review Board
                'approved',      // Approved by ≥5 validators, awaiting embedding
                'embedded',      // Embedded in Pinecone — eligible for Amen retrieval
                'rejected',      // Rejected by review board
            ])->default('submitted')->index();

            $table->json('reviewed_by')->nullable();       // Array of admin user IDs who reviewed
            $table->unsignedSmallInteger('review_count')->default(0);

            // Pinecone vector storage
            $table->string('pinecone_id', 100)->nullable()->unique();
            $table->timestamp('embedded_at')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'status']);
            $table->index(['status', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_contributions');
    }
};
