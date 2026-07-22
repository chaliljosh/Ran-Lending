<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    protected $table = 'otp_verifications';
    public $timestamps = false;

    protected $fillable = ['email', 'otp', 'type', 'is_used', 'expires_at'];
}