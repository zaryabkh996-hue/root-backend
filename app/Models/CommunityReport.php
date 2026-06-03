<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityReport extends Model
{
    protected $table = 'community_reports';

    protected $fillable = [
        'reporter_id',
        'reported_user_id',
        'item_type',
        'item_id',
        'reason',
        'status',
        'warning_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who reported the post.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the user who is reported.
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_user_id');
    }

    /**
     * Get the actual reported item (thread or reply).
     */
    public function getItemAttribute()
    {
        if ($this->item_type === 'thread') {
            return CommunityThread::find($this->item_id);
        } elseif ($this->item_type === 'reply') {
            return CommunityReply::find($this->item_id);
        }
        return null;
    }
}
