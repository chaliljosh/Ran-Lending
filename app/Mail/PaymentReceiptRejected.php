<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class PaymentReceiptRejected extends Mailable
{
    public $loan;
    public $displayName;
    public $loanCode;
    public $installmentNo;
    public $amountDue;
    public $remarks;

    public function __construct($loan, string $displayName, int $installmentNo, float $amountDue, string $remarks)
    {
        $this->loan = $loan;
        $this->displayName = $displayName;
        $this->installmentNo = $installmentNo;
        $this->amountDue = $amountDue;
        $this->remarks = $remarks;

        // Parehong format ng admin page: LN- + unang 8 chars ng uuid
        $this->loanCode = 'LN-' . strtoupper(substr($loan->uuid, 0, 8));
    }

    public function build()
    {
        return $this->subject('Please re-upload your receipt — ' . $this->loanCode)
            ->view('emails.payment-receipt-rejected');
    }
}