<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors admin_notification_reads — parehong structure, pero para
     * sa borrower side. Ang notification_key ay isang unique string
     * per-notification (hal. "payment:14"), hindi foreign key sa isang
     * hiwalay na "notifications" table, dahil ang mga notifications
     * mismo ay dynamically kino-compute mula sa loan_payment_schedules
     * (kagaya rin ng ginagawa ng Admin side mula sa iba't ibang tables).
     */
    public function up(): void
    {
        Schema::create('borrower_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('notification_key');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'notification_key']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrower_notification_reads');
    }
};