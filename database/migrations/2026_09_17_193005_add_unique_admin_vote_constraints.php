<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detailsapproval', function (Blueprint $table) {
            $table->unique(['detail_id', 'admin_id'], 'unique_detail_admin');
        });

        Schema::table('loan_approvals', function (Blueprint $table) {
            $table->unique(['application_id', 'admin_id'], 'unique_application_admin');
        });
    }

    public function down(): void
    {
        Schema::table('detailsapproval', function (Blueprint $table) {
            $table->dropUnique('unique_detail_admin');
        });

        Schema::table('loan_approvals', function (Blueprint $table) {
            $table->dropUnique('unique_application_admin');
        });
    }
};