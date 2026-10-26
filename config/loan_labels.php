<?php

/*
|--------------------------------------------------------------------------
| Loan application labels
|--------------------------------------------------------------------------
| Iisang source ng "code => label" para sa Apply for a Loan form.
| Ginagamit ng admin side (AdminController::labelFor) para ipakita ang
| readable na label imbes na raw code. Pwede mo ring gamitin sa borrower
| blade sa susunod para hindi na doble ang listahan.
|
| Pagkatapos baguhin ang file na ito, patakbuhin:
|     php artisan config:clear
*/

return [

    'loan_purposes' => [
        'business_capital'   => 'Business Capital',
        'education'          => 'Education',
        'medical_expenses'   => 'Medical Expenses',
        'home_improvement'   => 'Home Improvement',
        'debt_consolidation' => 'Debt Consolidation',
        'emergency'          => 'Emergency',
        'travel'             => 'Travel',
        'wedding'            => 'Wedding',
        'vehicle_purchase'   => 'Vehicle Purchase',
        'appliance_purchase' => 'Appliance / Electronics',
        'personal_expenses'  => 'Personal Expenses',
        'others'             => 'Others',
    ],

    'referral_sources' => [
        'facebook'      => 'Facebook',
        'instagram'     => 'Instagram',
        'tiktok'        => 'TikTok',
        'friend_family' => 'Friend / Family Referral',
        'flyer'         => 'Flyer / Poster',
        'word_of_mouth' => 'Word of Mouth',
        'google'        => 'Google Search',
        'others'        => 'Others',
    ],

    'id_types' => [
        'national_id'     => 'National ID (PhilSys)',
        'passport'        => 'Passport',
        'drivers_license' => "Driver's License",
        'umid'            => 'UMID',
        'sss'             => 'SSS ID',
        'tin'             => 'TIN ID',
        'postal'          => 'Postal ID',
        'voters'          => "Voter's ID",
        'prc'             => 'PRC ID',
        'others'          => 'Others',
    ],

    'employment_statuses' => [
        'employed'      => 'Employed',
        'self_employed' => 'Self-Employed',
        'unemployed'    => 'Unemployed',
        'student'       => 'Student',
        'retired'       => 'Retired',
    ],

    'primary_sources' => [
        'family_support'     => 'Family Support',
        'savings'            => 'Savings',
        'allowance'          => 'Allowance',
        'scholarship'        => 'Scholarship',
        'pension'            => 'Pension',
        'remittance'         => 'Remittance',
        'odd_jobs'           => 'Part-time / Odd Jobs',
        'rental_investments' => 'Rental / Investments',
    ],

    'years_in_company' => [
        '0-5'   => '0 – 5 years',
        '6-10'  => '6 – 10 years',
        '11-15' => '11 – 15 years',
        '16-20' => '16 – 20 years',
        '21+'   => 'More than 20 years',
    ],

    // Ang ibig sabihin ng company_name depende sa employment status
    'company_name_labels' => [
        'employed'      => 'Employer / Company Name',
        'self_employed' => 'Business Name',
        'student'       => 'School Name',
    ],

    // Ang ibig sabihin ng position depende sa employment status
    'position_labels' => [
        'employed'      => 'Occupation / Job Title',
        'self_employed' => 'Occupation / Business Type',
    ],

    // Ang ibig sabihin ng years_in_company depende sa employment status
    'years_labels' => [
        'employed'      => 'Years Employed',
        'self_employed' => 'Years in Business',
    ],

];