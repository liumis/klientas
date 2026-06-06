<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->uuid('marksign_uuid')->nullable()->after('id')->index();
            $table->text('signing_url')->nullable()->after('marksign_uuid');
            $table->string('signed_pdf_path')->nullable()->after('signing_url');
            $table->string('status')->default('pending')->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'marksign_uuid',
                'signing_url',
                'signed_pdf_path'
            ]);
        });
    }
};
