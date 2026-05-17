<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class UserProgress extends Model
{
    protected $table = 'user_progress';

    protected $fillable = [
        'user_id',
        'current_module_id',
        'completed_modules',
        'unlocked_stages',
        'completed_stages',
        'feedback_entries',
        'journal_entries',
        'afro_score',
        'user_persona',
        'lifecycle_phase',
        'started_at',
        'last_active_at',
    ];

    protected $casts = [
        'completed_modules' => 'array',
        'unlocked_stages'   => 'array',
        'completed_stages'  => 'array',
        'feedback_entries'  => 'array',
        'started_at'        => 'datetime',
        'last_active_at'    => 'datetime',
    ];

    // Journal entries are stored encrypted — cast manually
    public function getJournalEntriesAttribute(?string $value): array
    {
        if (empty($value)) {
            return [];
        }
        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function setJournalEntriesAttribute(array $value): void
    {
        $this->attributes['journal_entries'] = Crypt::encryptString(json_encode($value));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
