<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Binubura lahat ng test loan na ginawa ng CreditTestSeeder.
 * Run:  php artisan db:seed --class=CreditTestCleanupSeeder
 */
class CreditTestCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $ids = DB::table('loan_applications')
            ->where('loan_purpose_other', CreditTestSeeder::MARKER)
            ->pluck('application_id');

        DB::transaction(function () use ($ids) {
            DB::table('loan_penalties')->whereIn('application_id', $ids)->delete();
            DB::table('loan_payment_schedules')->whereIn('application_id', $ids)->delete();
            DB::table('loan_applications')->whereIn('application_id', $ids)->delete();
        });

        $this->command?->info('Binura ang ' . $ids->count() . ' test loan(s).');
    }
}