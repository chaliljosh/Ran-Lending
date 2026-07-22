<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detailscomments', function (Blueprint $table) {
            $table->id('comment_id');
            $table->foreignId('detail_id')->constrained('borrowerpersonaldetails', 'detail_id');
            $table->foreignId('admin_id')->constrained('users', 'user_id');
            $table->text('comment');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detailscomments');
    }
};