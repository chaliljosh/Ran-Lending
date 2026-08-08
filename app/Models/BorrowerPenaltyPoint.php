<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowerPenaltyPoint extends Model
{
    protected $fillable = [
        'user_id', 'points', 'disqualified_at', 'settled_at', 'reapplication_eligible_at',
    ];

    protected $casts = [
        'disqualified_at' => 'datetime',
        'settled_at' => 'datetime',
        'reapplication_eligible_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // True kung disqualified pa rin ang borrower ngayon.
    public function isDisqualified(): bool
    {
        if (!$this->disqualified_at) {
            return false;
        }
        if ($this->reapplication_eligible_at && now()->greaterThanOrEqualTo($this->reapplication_eligible_at)) {
            return false;
        }
        return true;
    }
}