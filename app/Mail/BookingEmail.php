<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingEmail extends Mailable
{
    public function __construct(
        protected Booking $booking,
        protected string $type, // 'created', 'cancelled', 'updated'
        protected string $recipientEmail,
        protected string $recipientName,
        protected ?string $recipientRole = null // 'customer' or 'custodian'
    ) {}

    public function envelope(): Envelope
    {
        $subject = match($this->type) {
            'created' => 'New Booking Confirmation - OurRoots.Africa',
            'cancelled' => 'Booking Cancelled - OurRoots.Africa',
            'updated' => 'Booking Updated - OurRoots.Africa',
            default => 'Booking Notification - OurRoots.Africa'
        };

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address', 'noreply@ourroots.africa'),
        );
    }

    public function content(): Content
    {
        $viewMap = [
            'created' => 'emails.booking-created',
            'cancelled' => 'emails.booking-cancelled',
            'updated' => 'emails.booking-updated',
        ];

        return new Content(
            view: $viewMap[$this->type] ?? 'emails.booking-created',
            with: [
                'booking' => $this->booking,
                'recipientName' => $this->recipientName,
                'recipientRole' => $this->recipientRole,
                'bookingDate' => $this->booking->booking_date->format('F j, Y'),
                'bookingTime' => $this->booking->booking_time,
                'customerName' => $this->booking->user->name,
                'custodianName' => $this->booking->custodian->name,
                'bookingMessage' => $this->booking->message,
            ],
        );
    }
}
