<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\WhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Payment received for your GG-TECH order :number', ['number' => $this->order->number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.payments.confirmed',
            with: ['whatsappUrl' => WhatsApp::orderConfirmation($this->order)],
        );
    }
}
