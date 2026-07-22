<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comakerinfo', function (Blueprint $table) {
            $table->id('comaker_id');
            $table->foreignId('detail_id')->constrained('borrowerpersonaldetails', 'detail_id');
            $table->string('last_name', 100)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('middle_name', 100)->nullable();
            $table->enum('relationship', ['spouse','parent','sibling','child','relative','friend','colleague'])->nullable();
            $table->string('contact_number', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comakerinfo');
    }
};