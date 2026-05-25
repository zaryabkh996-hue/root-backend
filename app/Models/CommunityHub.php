<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CommunityHub extends Model
{
    protected $table = 'community_hubs';

    protected $fillable = [
        'name',
        'slug',
        'adinkra',
        'emoji',
        'description',
        'access_level', // 'free', 'community', 'preparation', 'locked'
        'access_label',
        'border_color',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function threads(): HasMany
    {
        return $this->hasMany(CommunityThread::class, 'hub_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'hub_members', 'hub_id', 'user_id')
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMembersCount()
    {
        return $this->members()->count();
    }

    public function getActiveThreadsCount()
    {
        return $this->threads()->where('is_active', true)->count();
    }

    public function userIsMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }
}
