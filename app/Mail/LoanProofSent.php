<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class LoanProofSent extends Mailable
{
    public $loan;
    public $displayName;
    public $loanCode;
    public $proofPath;
    public $proofMime;
    public $proofFilename;

    public function __construct($loan, string $displayName, string $proofPath, string $proofMime, string $proofFilename)
    {
        $this->loan = $loan;
        $this->displayName = $displayName;
        $this->proofPath = $proofPath;
        $this->proofMime = $proofMime;
        $this->proofFilename = $proofFilename;

        // Parehong format ng admin page: LN- + unang 8 chars ng uuid
        $this->loanCode = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));
    }

    public function build()
    {
        return $this->subject('Proof of loan release — ' . $this->loanCode)
            ->view('emails.loan-proof-sent')
            ->attach($this->proofPath, [
                'as'   => $this->proofFilename,
                'mime' => $this->proofMime,
            ]);
    }
}