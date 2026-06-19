<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeCitation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'contribution_id',
        'custodian_id',
        'client_id',
        'conversation_id',
        'pinecone_score',
        'payout_amount',
        'payout_status',
        'cited_at',
    ];

    /**
     * Get the attributes that should be cast.
     */
    public function casts(): array
    {
        return [
            'pinecone_score' => 'float',
            'payout_amount'  => 'float',
            'cited_at'       => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    /**
     * The Knowledge Bank fragment that was cited.
     */
    public function contribution(): BelongsTo
    {
        return $this->belongsTo(KnowledgeContribution::class, 'contribution_id');
    }

    /**
     * The custodian who receives the payout.
     */
    public function custodian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    /**
     * The client who triggered the citation.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────

    /**
     * Citations pending payout.
     */
    public function scopePending($query)
    {
        return $query->where('payout_status', 'pending');
    }

    /**
     * Citations that have been paid.
     */
    public function scopePaid($query)
    {
        return $query->where('payout_status', 'paid');
    }

    /**
     * Citations for a specific custodian.
     */
    public function scopeForCustodian($query, int $custodianId)
    {
        return $query->where('custodian_id', $custodianId);
    }
}
