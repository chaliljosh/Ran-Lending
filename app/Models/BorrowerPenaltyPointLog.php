<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowerPenaltyPointLog extends Model
{
    protected $fillable = ['user_id', 'active_loan_id', 'reason'];
}