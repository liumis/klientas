<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PARTNERS
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('address');
            $table->string('company_code');
            $table->timestamps();
        });

        // GARAGES
        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('wheels_agent')->nullable();
            $table->string('wheels_source')->nullable();
            $table->timestamps();
        });

        // CLAIMS
        Schema::create('claims', function (Blueprint $table) {
            $table->id();

            // Marksign
            $table->uuid('marksign_uuid')->nullable()->index();
            $table->text('signing_url')->nullable();
            $table->string('signed_pdf_path')->nullable();

            // Basic info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('personal_code');
            $table->date('birth_date');
            $table->string('license_number');
            $table->date('license_expires_at');

            $table->string('claim_number');
            $table->string('address');
            $table->string('phone');
            $table->string('email');

            $table->json('documents')->nullable();

            // Status (final version)
            $table->string('status')->default('pending');

            // Relations
            $table->foreignId('partner_id')
                ->nullable()
                ->constrained('partners')
                ->nullOnDelete();

            $table->foreignId('garage_id')
                ->nullable()
                ->constrained('garages')
                ->nullOnDelete();

            // Rental
            $table->date('rental_start')->nullable();
            $table->date('rental_end')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
        Schema::dropIfExists('garages');
        Schema::dropIfExists('partners');
    }
};