<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_penalties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schedule_id')
                ->constrained('loan_payment_schedules')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id')
                ->references('application_id')->on('loan_applications')
                ->cascadeOnDelete();

            $table->unsignedInteger('days_late');
            $table->unsignedInteger('late_fee_periods')->default(0);
            $table->decimal('penalty_amount', 12, 2);
            $table->enum('status', ['unpaid', 'paid', 'waived'])->default('unpaid');

            $table->unsignedBigInteger('waived_by')->nullable();
            $table->foreign('waived_by')->references('user_id')->on('users')->nullOnDelete();

            $table->string('waived_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_penalties');
    }
};