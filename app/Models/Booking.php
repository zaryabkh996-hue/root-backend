<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'custodian_id',
        'booking_date',
        'booking_time',
        'message',
        'status',
        'session_type',
        'session_duration',
        'platform_link',
        'booking_reference',
        'amount_charged_usd',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'amount_charged_usd' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_reference)) {
                $booking->booking_reference = self::generateUniqueReference();
            }
        });
    }

    public static function generateUniqueReference()
    {
        $year = date('Y');
        do {
            $random = '';
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            for ($i = 0; $i < 4; $i++) {
                $random .= $characters[rand(0, strlen($characters) - 1)];
            }
            $reference = "OR-{$year}-{$random}";
        } while (self::where('booking_reference', $reference)->exists());

        return $reference;
    }

    /**
     * Get the user who made the booking
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the custodian for this booking
     */
    public function custodian()
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }
}
