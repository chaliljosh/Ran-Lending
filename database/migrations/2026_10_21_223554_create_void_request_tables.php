<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('void_requests', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('schedule_id');
            $t->unsignedBigInteger('application_id');
            $t->text('reason');
            $t->unsignedBigInteger('requested_by');
            $t->string('status', 20)->default('pending');
            $t->timestamp('resolved_at')->nullable();
            $t->timestamps();

            $t->index(['application_id', 'status']);
        });

        Schema::create('void_request_votes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('void_request_id');
            $t->unsignedBigInteger('admin_id');
            $t->string('decision', 10);
            $t->string('remarks', 255)->nullable();
            $t->timestamp('voted_at')->useCurrent();

            $t->unique(['void_request_id', 'admin_id']);
            $t->index('void_request_id');
        });

        DB::statement("
            CREATE UNIQUE INDEX void_requests_one_active_per_schedule
            ON void_requests (schedule_id)
            WHERE status IN ('pending', 'on_hold')
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS void_requests_one_active_per_schedule');
        Schema::dropIfExists('void_request_votes');
        Schema::dropIfExists('void_requests');
    }
};