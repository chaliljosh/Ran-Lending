<?php

// Ilagay sa: app/Mail/LoanApplicationReceived.php
//
// Ipinapadala sa borrower pagkatapos niyang mag-submit ng Apply for a Loan form.
// View: resources/views/emails/loan-application-received.blade.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LoanApplicationReceived extends Mailable
{
    use Queueable;

    /**
     * @param object $loan          Row from `loan_applications` (DB::table()->first())
     * @param string $displayName   First name ng borrower
     * @param string $purposeLabel  Readable na purpose (galing sa config/loan_labels.php)
     */
    public function __construct(
        public object $loan,
        public string $displayName,
        public string $purposeLabel,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your loan application',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loan-application-received',
            with: [
                'loan'         => $this->loan,
                'displayName'  => $this->displayName,
                'purposeLabel' => $this->purposeLabel,
                // Parehong format ng proof of release email: LN- + unang 8 chars ng uuid
                'loanCode'     => 'LN-' . strtoupper(substr($this->loan->uuid, 0, 8)),
            ],
        );
    }
}