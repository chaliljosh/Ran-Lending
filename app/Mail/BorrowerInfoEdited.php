<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class BorrowerInfoEdited extends Mailable
{
    public $loan;
    public $displayName;
    public $changes;
    public $reason;
    public $loanCode;

    /**
     * $changes = [ ['label' => 'Barangay', 'old' => '...', 'new' => '...'], ... ]
     */
    public function __construct($loan, string $displayName, array $changes, string $reason)
    {
        $this->loan = $loan;
        $this->displayName = $displayName;
        $this->changes = $changes;
        $this->reason = $reason;

        // Same format as the other emails: LN- + first 8 chars of the uuid
        $this->loanCode = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));
    }

    public function build()
    {
        return $this->subject('Your information was updated — ' . $this->loanCode)
            ->view('emails.borrower-info-edited');
    }
}