<?php

// Ilagay sa: app/Mail/PaymentRecorded.php
//
// Ipinapadala kapag nag-record ng payment ang admin (markInstallmentPaid).
// Dalawang variant: normal na installment payment, at fully paid/completed
// na loan (mas espesyal na message).

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PaymentRecorded extends Mailable
{
    use Queueable;

    /**
     * @param object $loan            Row from `loan_applications`
     * @param string $borrowerName    Pangalan ng borrower
     * @param int    $installmentNo   Installment number na binayaran
     * @param float  $amountPaid      Amount na na-record
     * @param bool   $isFullyPaid     True kung ito na yung huling installment (loan completed)
     * @param string|null $nextDueDate  Y-m-d ng susunod na installment (null kung fully paid na)
     * @param float|null  $nextDueAmount  Amount ng susunod na installment
     */
    public function __construct(
        public object $loan,
        public string $borrowerName,
        public int $installmentNo,
        public float $amountPaid,
        public bool $isFullyPaid,
        public ?string $nextDueDate = null,
        public ?float $nextDueAmount = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isFullyPaid
                ? 'Congratulations — your loan is fully paid!'
                : 'Payment received — Installment #' . $this->installmentNo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-recorded',
            with: [
                'loan'           => $this->loan,
                'borrowerName'   => $this->borrowerName,
                'installmentNo'  => $this->installmentNo,
                'amountPaid'     => $this->amountPaid,
                'isFullyPaid'    => $this->isFullyPaid,
                'nextDueDate'    => $this->nextDueDate,
                'nextDueAmount'  => $this->nextDueAmount,
            ],
        );
    }
}