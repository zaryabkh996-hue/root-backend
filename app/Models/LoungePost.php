<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoungePost extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'category',
        'likes_count',
        'replies_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(LoungePostLike::class, 'post_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(LoungePostReply::class, 'post_id');
    }

    public function isLikedBy($userId): bool
    {
        if (!$userId) return false;
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
