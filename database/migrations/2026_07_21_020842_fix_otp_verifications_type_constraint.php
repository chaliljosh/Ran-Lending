<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tanggalin muna ang lumang constraint
        DB::statement('ALTER TABLE otp_verifications DROP CONSTRAINT otp_verifications_type_check');

        // 2. Ngayon ligtas na i-update ang lumang data
        DB::table('otp_verifications')->where('type', 'registration')->update(['type' => 'register']);

        // 3. Saka lang ilagay ang bagong constraint
        DB::statement("ALTER TABLE otp_verifications ADD CONSTRAINT otp_verifications_type_check CHECK (type IN ('register', 'login', 'forgot_password', 'change_password'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE otp_verifications DROP CONSTRAINT otp_verifications_type_check');
        DB::table('otp_verifications')->where('type', 'register')->update(['type' => 'registration']);
        DB::statement("ALTER TABLE otp_verifications ADD CONSTRAINT otp_verifications_type_check CHECK (type IN ('forgot_password', 'change_password', 'registration', 'login'))");
    }
};