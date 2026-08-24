<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            // e.g. "verify:12", "loan:5", "onhold:7", "overdue:3"
            $table->string('notification_key');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['admin_id', 'notification_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_reads');
    }
};