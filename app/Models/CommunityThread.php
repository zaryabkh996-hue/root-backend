<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityThread extends Model
{
    protected $table = 'community_threads';

    protected $fillable = [
        'hub_id',
        'user_id',
        'title',
        'content',
        'location',
        'user_stage',
        'user_tier',
        'is_active',
        'is_pinned',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_pinned' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(CommunityHub::class, 'hub_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommunityReply::class, 'thread_id')->orderBy('created_at', 'asc');
    }

    public function latestReply(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CommunityReply::class, 'thread_id')->latestOfMany();
    }

    public function getRepliesCount()
    {
        return $this->replies()->count();
    }

    public function getCustodianRepliesCount()
    {
        return $this->replies()->whereHas('author', function ($query) {
            $query->where('role', 'custodian');
        })->count();
    }

    public function getLastReply()
    {
        return $this->replies()->latest()->first();
    }
}
