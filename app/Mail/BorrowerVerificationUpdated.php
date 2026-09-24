<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BorrowerVerificationUpdated extends Mailable
{
    use Queueable;

    /**
     * @param object $borrower       Row mula sa `users` (may email, name)
     * @param string $borrowerName   Pangalan na ipapakita
     * @param string $status         'approved' | 'rejected' | 'on_hold'
     * @param array  $remarks        Mga note ng admins na nag-reject (strings).
     *                               May default na [] kaya hindi masisira ang
     *                               mga existing na tawag sa Mailable na ito.
     */
    public function __construct(
        public object $borrower,
        public string $borrowerName,
        public string $status,
        public array $remarks = [],
    ) {
    }

    public function envelope(): Envelope
    {
        $subjects = [
            'approved' => 'Your account has been verified!',
            'rejected' => 'Update on your account verification',
            'on_hold'  => 'Your account verification needs attention',
        ];

        return new Envelope(
            subject: $subjects[$this->status] ?? 'Update on your account verification',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.borrower-verification-updated',
            with: [
                'borrower'     => $this->borrower,
                'borrowerName' => $this->borrowerName,
                'status'       => $this->status,
                'remarks'      => $this->remarks,
            ],
        );
    }
}