<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->foreignId('user_id')->constrained('users', 'user_id');
            $table->decimal('loan_amount', 10, 2);
            $table->integer('loan_term');
            $table->string('loan_purpose', 100);
            $table->string('referral_source', 100);
            $table->string('referral_name', 255)->nullable();
            $table->decimal('interest_rate', 5, 2)->default(5.00);
            $table->decimal('total_interest', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('monthly_payment', 10, 2);
            $table->enum('status', ['pending','approved','rejected','on_hold','active','completed','defaulted'])->default('pending');
            $table->dateTime('applied_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};