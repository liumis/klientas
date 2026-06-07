<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('repair_vehicle_plates');
            $table->string('personal_code');
            $table->date('birth_date');
            $table->string('license_number');
            $table->date('license_expires_at');
            $table->string('id_or_passport_number');
            $table->date('id_or_passport_expires_at');
            $table->string('bank_card_number');
            $table->date('bank_card_expires_at');

            $table->string('claim_number');
            $table->string('address');
            $table->string('phone');
            $table->string('email');

            $table->json('documents')->nullable();

            $table->string('status')->default('uzklausa');

            $table->date('rental_start')->nullable();
            $table->date('rental_end')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
