<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_payment_schedules', function (Blueprint $table) {
            $table->id();

            // loan_applications' PK is "application_id", not "id"
            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id')
                ->references('application_id')->on('loan_applications')
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('installment_no'); // 1, 2, 3...
            $table->date('due_date');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->enum('status', ['pending', 'paid', 'late'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payment_schedules');
    }
};