<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmenConversation extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'conversation_id',
        'role',
        'content',
        'fragment_used',
        'contribution_id',
        'model_used',
        'tokens_used',
    ];

    /**
     * Get the attributes that should be cast.
     */
    public function casts(): array
    {
        return [
            'content'       => 'encrypted',
            'fragment_used' => 'boolean',
            'tokens_used'   => 'integer',
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────

    /**
     * The user (client) in this conversation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The Knowledge Bank contribution cited in this message (if any).
     */
    public function contribution(): BelongsTo
    {
        return $this->belongsTo(KnowledgeContribution::class, 'contribution_id');
    }

    // ──────────────────────────────────────────────────────────────
    // Scopes
    // ──────────────────────────────────────────────────────────────

    /**
     * Messages in a specific conversation session.
     */
    public function scopeForSession($query, string $conversationId)
    {
        return $query->where('conversation_id', $conversationId)
                     ->orderBy('created_at', 'asc');
    }

    /**
     * Only user messages (for fine-tuning dataset).
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Only assistant messages (for fine-tuning dataset).
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Messages that used Knowledge Bank fragments.
     */
    public function scopeWithFragments($query)
    {
        return $query->where('fragment_used', true);
    }
}
