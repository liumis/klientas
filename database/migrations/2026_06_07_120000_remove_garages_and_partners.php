<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claims')) {
            Schema::table('claims', function (Blueprint $table) {
                if (Schema::hasColumn('claims', 'garage_id')) {
                    $table->dropForeign(['garage_id']);
                    $table->dropColumn('garage_id');
                }

                if (Schema::hasColumn('claims', 'partner_id')) {
                    $table->dropForeign(['partner_id']);
                    $table->dropColumn('partner_id');
                }
            });
        }

        Schema::dropIfExists('garages');
        Schema::dropIfExists('partners');
    }

    public function down(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('address');
            $table->string('company_code');
            $table->timestamps();
        });

        Schema::create('garages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('wheels_agent')->nullable();
            $table->string('wheels_source')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('claims')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->foreignId('partner_id')
                    ->nullable()
                    ->after('address')
                    ->constrained('partners')
                    ->nullOnDelete();

                $table->foreignId('garage_id')
                    ->nullable()
                    ->after('partner_id')
                    ->constrained('garages')
                    ->nullOnDelete();
            });
        }
    }
};
