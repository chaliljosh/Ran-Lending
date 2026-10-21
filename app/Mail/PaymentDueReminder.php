<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Reminder sa borrower na malapit na ang due date ng bayad.
class PaymentDueReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $loanId,
        public string $borrowerName,
        public int $installmentNo,
        public float $amount,
        public string $dueDate,
        public int $daysLeft,
    ) {}

    public function envelope(): Envelope
    {
        if ($this->daysLeft <= 0) {
            $when = 'today';
        } elseif ($this->daysLeft === 1) {
            $when = 'tomorrow';
        } else {
            $when = 'in ' . $this->daysLeft . ' days';
        }

        return new Envelope(
            subject: "Payment reminder: installment #{$this->installmentNo} is due {$when}"
        );
    }

    public function content(): Content
    {
        // Kung iba ang folder ng ibang email views mo, ito ang papalitan.
        return new Content(view: 'emails.payment-due-reminder');
    }
}