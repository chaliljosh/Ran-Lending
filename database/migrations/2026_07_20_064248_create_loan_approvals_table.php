<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_approvals', function (Blueprint $table) {
            $table->id('approval_id');
            $table->foreignId('application_id')->constrained('loan_applications', 'application_id');
            $table->foreignId('admin_id')->constrained('users', 'user_id');
            $table->string('admin_role', 20)->nullable();
            $table->enum('decision', ['approved', 'rejected']);
            $table->text('remarks')->nullable();
            $table->dateTime('reviewed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_approvals');
    }
};