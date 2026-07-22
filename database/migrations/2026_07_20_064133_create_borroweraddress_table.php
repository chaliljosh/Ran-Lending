<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borroweraddress', function (Blueprint $table) {
            $table->id('address_id');
            $table->foreignId('detail_id')->constrained('borrowerpersonaldetails', 'detail_id');
            $table->string('region', 80)->nullable();
            $table->string('province', 80)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('barangay', 80)->nullable();
            $table->integer('zipcode')->nullable();
            $table->text('home_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borroweraddress');
    }
};