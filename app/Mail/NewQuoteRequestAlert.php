<?php

namespace App\Mail;

use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewQuoteRequestAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public QuoteRequest $quote) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nouvelle demande de devis {$this->quote->number} — {$this->quote->company_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.quotes.alert',
        );
    }
}
