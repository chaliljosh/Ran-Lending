<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_approval_comments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->foreignId('application_id')->constrained('loan_applications', 'application_id');
            $table->foreignId('admin_id')->constrained('users', 'user_id');
            $table->text('comment');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_approval_comments');
    }
};