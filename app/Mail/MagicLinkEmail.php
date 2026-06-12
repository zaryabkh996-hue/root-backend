<?php

namespace App\Mail;

use App\Models\MagicLink;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class MagicLinkEmail extends Mailable
{
    public function __construct(
        protected MagicLink $magicLink,
        protected string $magicUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your OurRoots.Africa Magic Link — Welcome Home',
            from: config('mail.from.address', 'noreply@ourroots.africa'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
            with: [
                'name' => $this->magicLink->name,
                'magicUrl' => $this->magicUrl,
                'expiresAt' => $this->magicLink->expires_at->format('g:i A'),
            ],
        );
    }
}
