<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->boolean('send_notifications')->default(false);
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        if (! Schema::hasTable('claims')) {
            Schema::create('claims', function (Blueprint $table) {
                $table->id();

                $table->string('first_name');
                $table->string('last_name');
                $table->string('repair_vehicle_plates')->default('');
                $table->string('personal_code');
                $table->date('birth_date');
                $table->string('license_number');
                $table->date('license_expires_at');
                $table->string('id_or_passport_number')->default('');
                $table->date('id_or_passport_expires_at')->nullable();
                $table->string('bank_card_number')->default('');
                $table->date('bank_card_expires_at')->nullable();

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
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
