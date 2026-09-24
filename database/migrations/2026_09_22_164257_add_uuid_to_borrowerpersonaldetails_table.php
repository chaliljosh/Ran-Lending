<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrowerpersonaldetails', function (Blueprint $table) {
            // Nullable muna dahil may existing rows na — pupunan natin
            // sa susunod na hakbang, tapos saka gagawing unique + not-null.
            $table->uuid('uuid')->nullable()->after('detail_id');
        });

        // Backfill — bigyan ng UUID ang lahat ng existing rows.
        DB::table('borrowerpersonaldetails')->whereNull('uuid')->orderBy('detail_id')->get()->each(function ($row) {
            DB::table('borrowerpersonaldetails')
                ->where('detail_id', $row->detail_id)
                ->update(['uuid' => (string) Str::uuid()]);
        });

        Schema::table('borrowerpersonaldetails', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('borrowerpersonaldetails', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};