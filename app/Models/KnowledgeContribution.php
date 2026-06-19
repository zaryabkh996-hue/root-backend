<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeContribution extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'category',
        'ethnic_group',
        'region',
        'authority_role',
        'media_path',
        'media_type',
        'transcription',
        'consent_signed',
        'consent_signature',
        'consent_pdf_path',
        'status',
        'reviewed_by',
        'review_count',
        'pinecone_id',
        'embedded_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    public function casts(): array
    {
        return [
            'consent_signed' => 'boolean',
            'reviewed_by'    => 'array',
            'embedded_at'    => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    /**
     * The custodian who submitted this knowledge.
     */
    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * All citations where this fragment was used by Amen AI.
     */
    public function citations(): HasMany
    {
        return $this->hasMany(KnowledgeCitation::class, 'contribution_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────

    /**
     * Only fragments that have been embedded in Pinecone
     * and are eligible for Amen AI retrieval.
     */
    public function scopeEmbedded($query)
    {
        return $query->where('status', 'embedded');
    }

    /**
     * Fragments awaiting review.
     */
    public function scopePendingReview($query)
    {
        return $query->whereIn('status', ['submitted', 'under_review']);
    }

    /**
     * Fragments approved but not yet embedded.
     */
    public function scopeApprovedNotEmbedded($query)
    {
        return $query->where('status', 'approved');
    }

    // ──────────────────────────────────────────────────────────────
    // Accessors
    // ──────────────────────────────────────────────────────────────

    /**
     * Total earnings from citations for this fragment.
     */
    public function getTotalEarningsAttribute(): float
    {
        return $this->citations()
            ->where('payout_status', 'paid')
            ->sum('payout_amount');
    }

    /**
     * Total citation count for this fragment.
     */
    public function getCitationCountAttribute(): int
    {
        return $this->citations()->count();
    }
}
