<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PaymentPartiallyRecorded extends Mailable
{
    public $loan;
    public $displayName;
    public $loanCode;
    public $installmentNo;
    public $amountDue;
    public $received;
    public $balance;
    public $remarks;

    public function __construct($loan, string $displayName, int $installmentNo, float $amountDue, float $received, float $balance, ?string $remarks = null)
    {
        $this->loan = $loan;
        $this->displayName = $displayName;
        $this->installmentNo = $installmentNo;
        $this->amountDue = $amountDue;
        $this->received = $received;
        $this->balance = $balance;
        $this->remarks = $remarks;

        // Parehong format ng admin page: LN- + unang 8 chars ng uuid
        $this->loanCode = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));
    }

    public function build()
    {
        return $this->subject('Partial payment received — ' . $this->loanCode)
            ->view('emails.payment-partially-recorded');
    }
}