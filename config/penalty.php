<?php

// Base sa official loan rules (Step 2: Loan Rules & Computations)
return [

    'interest_rate_monthly' => 0.05, // 5% monthly — dokumentasyon lang

    // LATE FEE: PHP50 every 7 days late
    'late_fee_amount' => 50.00,
    'late_fee_period_days' => 7,

    // PENALTY POINTS: 3 consecutive months ng zero-payment = 1 point
    'months_per_point' => 3,
    'max_penalty_points' => 3,

    // REAPPLICATION: settle balance muna, tapos maghintay ng ganitong
    // ilang araw
    'reapplication_wait_days' => 90,
];