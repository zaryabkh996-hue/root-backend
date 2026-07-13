<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    protected $fillable = [
        'email',
        'token',
        'name',
        'whatsapp',
        'quiz_data',
        'used',
        'expires_at',
        'used_at'
    ];

    protected $casts = [
        'quiz_data' => 'array',
        'used' => 'boolean',
        'expires_at' => 'datetime',
        'used_at' => 'datetime'
    ];

    /**
     * Check if magic link is valid (not used and not expired)
     */
    public function isValid(): bool
    {
        return !$this->used && now()->isBefore($this->expires_at);
    }

    /**
     * Mark as used
     */
    public function markAsUsed(): void
    {
        $this->update([
            'used' => true,
            'used_at' => now()
        ]);
    }

    /**
     * Generate unique token
     */
    public static function generateToken(): string
    {
        return hash('sha256', Str::random(64) . time());
    }
}
