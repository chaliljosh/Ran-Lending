<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowerworkinfo', function (Blueprint $table) {
            $table->id('work_id');
            $table->foreignId('detail_id')->constrained('borrowerpersonaldetails', 'detail_id');
            $table->string('company_name', 200)->nullable();
            $table->string('position', 80)->nullable();
            $table->enum('employment_status', ['employed','self_employed','unemployed','student','retired'])->nullable();
            $table->decimal('monthly_income', 12, 2)->nullable();
            $table->decimal('years_in_company', 4, 1)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowerworkinfo');
    }
};