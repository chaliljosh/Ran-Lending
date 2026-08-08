<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrower_penalty_points', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();

            $table->unsignedTinyInteger('points')->default(0);
            $table->timestamp('disqualified_at')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->timestamp('reapplication_eligible_at')->nullable();
            $table->timestamps();
        });

        Schema::create('borrower_penalty_point_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();

            $table->unsignedBigInteger('application_id');
            $table->foreign('application_id')->references('application_id')->on('loan_applications')->cascadeOnDelete();

            $table->string('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower_penalty_point_logs');
        Schema::dropIfExists('borrower_penalty_points');
    }
};