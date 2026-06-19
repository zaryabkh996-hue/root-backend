<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'subscription_tier',
        'stripe_id',
        'stripe_subscription_id',
        'stripe_price_id',
        'subscription_status',
        'subscription_ends_at',
        'status',
        'onboarded',
        'whatsapp',
        'instagram',
        'linkedin',
        'quiz_data',
        'email_verified_at',
        'auth0_id',
        'picture',
        'provider',
        'location',
        'country',
        'years_experience',
        'specialty',
        'avatar_class',
        'gradient_bg',
        'availability',
        'description',
        'tags',
        'price_from',
        'certification',
        'coc_status',
        'review_avg',
        'sessions_count',
        'short_bio',
        'about',
        'languages',
        'services',
        'testimonials',
        'bio',
        'bio_privacy',
        'travel_date',
        'travel_location',
        'diaspora_group',
        'learning_preference',
        'profile_visibility',
        'journey_photos_default',
        'show_score_publicly',
        'notification_preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'onboarded' => 'boolean',
            'quiz_data' => 'array',
            'tags' => 'array',
            'languages' => 'array',
            'services' => 'array',
            'testimonials' => 'array',
            'notification_preferences' => 'array',
            'subscription_ends_at' => 'datetime',
        ];
    }

    /**
     * Get the user's progress record.
     */
    public function progress()
    {
        return $this->hasOne(UserProgress::class);
    }

    /**
     * Get the bookings for this custodian.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'custodian_id');
    }
}
