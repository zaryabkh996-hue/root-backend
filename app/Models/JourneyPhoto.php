<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourneyPhoto extends Model
{
    protected $fillable = [
        'user_id',
        'url',
        'caption',
        'hub',
        'visibility',
    ];

    /**
     * Get the user that owns the journey photo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
