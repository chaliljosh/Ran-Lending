<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        // 1. Idagdag ang column (nullable muna, para hindi mag-error ang mga existing rows)
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('application_id');
        });

        // 2. I-backfill ang lahat ng existing rows
        DB::table('loan_applications')
            ->whereNull('uuid')
            ->orderBy('application_id')
            ->each(function ($row) {
                DB::table('loan_applications')
                    ->where('application_id', $row->application_id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};