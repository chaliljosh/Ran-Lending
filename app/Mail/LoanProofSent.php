<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class LoanProofSent extends Mailable
{
    public $loan;
    public $displayName;
    public $schedule;      // the installment row (loan_payment_schedules) this proof is for
    public $proofPath;
    public $proofMime;
    public $proofFilename;

    public function __construct($loan, string $displayName, $schedule, string $proofPath, string $proofMime, string $proofFilename)
    {
        $this->loan = $loan;
        $this->displayName = $displayName;
        $this->schedule = $schedule;
        $this->proofPath = $proofPath;
        $this->proofMime = $proofMime;
        $this->proofFilename = $proofFilename;
    }

    public function build()
    {
        return $this->subject('Proof of payment — Installment #' . (int) $this->schedule->installment_no)
            ->view('emails.loan-proof-sent')
            ->attach($this->proofPath, [
                'as'   => $this->proofFilename,
                'mime' => $this->proofMime,
            ]);
    }
}