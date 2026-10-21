<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Email sa borrower kapag na-void ng admin ang isang recorded payment.
class PaymentVoided extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $loan,
        public string $borrowerName,
        public int $installmentNo,
        public float $amount,
        public $voidedAt,
        public string $reason = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Payment voided');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-voided');
    }
}