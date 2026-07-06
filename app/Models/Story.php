<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory;

    protected $table = 'stories';

    protected $fillable = [
        'sanity_id',
        'title',
        'body',
        'author',
        'author_id',
        'status',
        'revision_note',
        'community_hub_id',
    ];

    protected $casts = [
        'author_id' => 'integer',
        'community_hub_id' => 'integer',
    ];

    /**
     * Relationship to the Author (User)
     */
    public function authorUser()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Relationship to the routed Community Hub
     */
    public function communityHub()
    {
        return $this->belongsTo(CommunityHub::class, 'community_hub_id');
    }
}
