<?php

// Ilagay sa: app/Mail/LoanStatusUpdated.php
//
// Isang Mailable para sa tatlong status: approved, rejected, on_hold.
// Ang subject at laman ng email ay nagbabago depende sa $status
// (tingnan ang resources/views/emails/loan-status.blade.php).
//
// Requires Laravel 9+ (Envelope / Content API).

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LoanStatusUpdated extends Mailable
{
    use Queueable;

    private const SUBJECTS = [
        'approved' => 'Your loan application has been approved',
        'rejected' => 'Update on your loan application',
        'on_hold'  => 'Your loan application is on hold',
    ];

    /**
     * @param object $loan          Row from `loan_applications` (DB::table()->first())
     * @param string $borrowerName  Pangalan ng borrower (users.name)
     * @param string $status        approved | rejected | on_hold
     */
    public function __construct(
        public object $loan,
        public string $borrowerName,
        public string $status,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: self::SUBJECTS[$this->status] ?? 'Update on your loan application',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loan-status',
            with: [
                'loan'         => $this->loan,
                'borrowerName' => $this->borrowerName,
                'status'       => $this->status,
            ],
        );
    }
}