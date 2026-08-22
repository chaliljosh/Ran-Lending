<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TestVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $adminIds = [1, 2, 3];

        $rejectedBorrowers = [
            ['user_id' => 21, 'first' => 'Liza', 'last' => 'Domingo'],
            ['user_id' => 22, 'first' => 'Rafael', 'last' => 'Villanueva'],
            ['user_id' => 23, 'first' => 'Bianca', 'last' => 'Ocampo'],
            ['user_id' => 24, 'first' => 'Ederson', 'last' => 'Manalo'],
            ['user_id' => 25, 'first' => 'Grace', 'last' => 'Bautista'],
            ['user_id' => 26, 'first' => 'Noel', 'last' => 'Pascual'],
            ['user_id' => 27, 'first' => 'Divina', 'last' => 'Salazar'],
            ['user_id' => 28, 'first' => 'Ronnie', 'last' => 'Castillo'],
            ['user_id' => 29, 'first' => 'Precious', 'last' => 'Gutierrez'],
            ['user_id' => 30, 'first' => 'Allan', 'last' => 'Navarro'],
            ['user_id' => 31, 'first' => 'Trisha', 'last' => 'Ferrer'],
        ];

        $onHoldBorrowers = [
            ['user_id' => 34, 'first' => 'Ivy', 'last' => 'Marasigan'],
            ['user_id' => 35, 'first' => 'Ferdinand', 'last' => 'Corpuz'],
            ['user_id' => 36, 'first' => 'Michelle', 'last' => 'Villareal'],
            ['user_id' => 37, 'first' => 'Bryan', 'last' => 'Padilla'],
            ['user_id' => 38, 'first' => 'Angelica', 'last' => 'Tolentino'],
            ['user_id' => 39, 'first' => 'Warren', 'last' => 'Sarmiento'],
            ['user_id' => 40, 'first' => 'Nicole', 'last' => 'Del Rosario'],
            ['user_id' => 41, 'first' => 'Emmanuel', 'last' => 'Lazaro'],
            ['user_id' => 42, 'first' => 'Jasmine', 'last' => 'Abad'],
            ['user_id' => 43, 'first' => 'Victor', 'last' => 'Cabrera'],
            ['user_id' => 44, 'first' => 'Katrina', 'last' => 'Molina'],
        ];

        $genders = ['male', 'female'];
        $civilStatuses = ['single', 'married'];

        DB::transaction(function () use ($rejectedBorrowers, $onHoldBorrowers, $adminIds, $genders, $civilStatuses) {

            foreach ($rejectedBorrowers as $i => $b) {
                DB::table('borrowerpersonaldetails')->updateOrInsert(
                    ['user_id' => $b['user_id']],
                    [
                        'last_name'      => $b['last'],
                        'first_name'     => $b['first'],
                        'middle_name'    => null,
                        'suffix'         => null,
                        'birthday'       => Carbon::now()->subYears(20 + ($i % 15))->subDays($i)->toDateString(),
                        'gender'         => $genders[$i % 2],
                        'civil_status'   => $civilStatuses[$i % 2],
                        'citizenship'    => 'Filipino',
                        'birth_country'  => 'Philippines',
                        'birth_province' => 'Bulacan',
                        'birth_city'     => 'Meycauayan',
                        'cellphone_num'  => '09' . str_pad((171000000 + $i * 37), 9, '0', STR_PAD_LEFT),
                        'id_type'        => 'umid',
                        'id_number'      => 'UMID-R' . str_pad(1000 + $i, 6, '0', STR_PAD_LEFT),
                        'id_file_path'   => null,
                        'monthly_income' => 15000 + ($i * 1500),
                        'email'          => strtolower($b['first'] . '.' . str_replace(' ', '', $b['last']) . '.rejtest' . $i . '@example.com'),
                        'status'         => 'rejected',
                    ]
                );

                $detailId = DB::table('borrowerpersonaldetails')
                    ->where('user_id', $b['user_id'])
                    ->value('detail_id');

                DB::table('detailsapproval')->where('detail_id', $detailId)->delete();

                foreach ($adminIds as $j => $adminId) {
                    DB::table('detailsapproval')->insert([
                        'detail_id'   => $detailId,
                        'admin_id'    => $adminId,
                        'admin_role'  => null,
                        'decision'    => 'rejected',
                        'remarks'     => 'Does not meet minimum income requirement.',
                        'reviewed_at' => Carbon::now()->subDays(20 - $i)->subHours($j),
                    ]);
                }
            }

            foreach ($onHoldBorrowers as $i => $b) {
                DB::table('borrowerpersonaldetails')->updateOrInsert(
                    ['user_id' => $b['user_id']],
                    [
                        'last_name'      => $b['last'],
                        'first_name'     => $b['first'],
                        'middle_name'    => null,
                        'suffix'         => null,
                        'birthday'       => Carbon::now()->subYears(20 + ($i % 15))->subDays($i)->toDateString(),
                        'gender'         => $genders[$i % 2],
                        'civil_status'   => $civilStatuses[$i % 2],
                        'citizenship'    => 'Filipino',
                        'birth_country'  => 'Philippines',
                        'birth_province' => 'Bulacan',
                        'birth_city'     => 'Meycauayan',
                        'cellphone_num'  => '09' . str_pad((181000000 + $i * 41), 9, '0', STR_PAD_LEFT),
                        'id_type'        => 'umid',
                        'id_number'      => 'UMID-H' . str_pad(1000 + $i, 6, '0', STR_PAD_LEFT),
                        'id_file_path'   => null,
                        'monthly_income' => 15000 + ($i * 1500),
                        'email'          => strtolower($b['first'] . '.' . str_replace(' ', '', $b['last']) . '.holdtest' . $i . '@example.com'),
                        'status'         => 'on_hold',
                    ]
                );

                $detailId = DB::table('borrowerpersonaldetails')
                    ->where('user_id', $b['user_id'])
                    ->value('detail_id');

                DB::table('detailsapproval')->where('detail_id', $detailId)->delete();

                if ($i % 2 === 0) {
                    $pattern = ['approved', 'rejected', 'approved'];
                } else {
                    $pattern = ['rejected', 'approved', 'rejected'];
                }

                foreach ($adminIds as $j => $adminId) {
                    $decision = $pattern[$j];

                    if ($decision === 'approved') {
                        $remarks = 'Documents look complete, no issues found.';
                    } else {
                        $remarks = 'Needs further verification of address proof.';
                    }

                    DB::table('detailsapproval')->insert([
                        'detail_id'   => $detailId,
                        'admin_id'    => $adminId,
                        'admin_role'  => null,
                        'decision'    => $decision,
                        'remarks'     => $remarks,
                        'reviewed_at' => Carbon::now()->subDays(10 - $i)->subHours($j),
                    ]);
                }
            }
        });

        $this->command->info('Done: 11 rejected + 11 on_hold borrowers seeded.');
    }
}