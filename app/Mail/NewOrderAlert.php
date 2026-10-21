<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Nouvelle commande {$this->order->number} — {$this->order->formatMoney($this->order->total)}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.alert',
        );
    }
}
