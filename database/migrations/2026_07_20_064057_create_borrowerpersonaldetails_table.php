<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowerpersonaldetails', function (Blueprint $table) {
            $table->id('detail_id');
            $table->foreignId('user_id')->unique()->constrained('users', 'user_id');
            $table->string('last_name', 45)->nullable();
            $table->string('first_name', 45)->nullable();
            $table->string('middle_name', 45)->nullable();
            $table->string('suffix', 10)->nullable();
            $table->date('birthday')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('civil_status', ['single', 'taken', 'married', 'widowed', 'separated'])->nullable();
            $table->string('citizenship', 100)->nullable();
            $table->string('birth_country', 45)->nullable();
            $table->string('birth_province', 45)->nullable();
            $table->string('birth_city', 45)->nullable();
            $table->string('cellphone_num', 20)->nullable();
            $table->enum('id_type', ['national_id','passport','drivers_license','umid','sss','tin','postal','voters','prc'])->nullable();
            $table->string('id_number', 45)->nullable();
            $table->string('id_file_path', 255)->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->string('email', 150)->nullable();
            $table->enum('status', ['incomplete','pending','approved','rejected','on_hold'])->default('incomplete');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowerpersonaldetails');
    }
};