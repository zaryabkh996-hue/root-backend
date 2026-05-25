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
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

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
