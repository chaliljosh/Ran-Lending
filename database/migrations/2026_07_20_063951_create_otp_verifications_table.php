<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email', 150)->nullable();
            $table->string('otp', 10)->nullable();
            $table->enum('type', ['forgot_password', 'change_password', 'register', 'login']);
            $table->boolean('is_used')->default(false);
            $table->dateTime('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_verifications');
    }
};