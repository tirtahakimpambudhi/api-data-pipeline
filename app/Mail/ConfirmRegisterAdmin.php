<?php

namespace App\Mail;

use App\Models\PendingAdminRegistrations;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Ramsey\Collection\Collection;

class ConfirmRegisterAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PendingAdminRegistrations $pending) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(env('MAIL_FROM_ADDRESS', 'hello@gmail.com'), env('MAIL_FROM_NAME', 'Laravel')),
            subject: 'Confirm Registration: Admin User'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.admin-confirmation',
            with: [
                'pending' => $this->pending,
            ],
        );
    }

    public function attachments(): array { return []; }
}
