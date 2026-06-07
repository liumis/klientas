<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claims')) {
            DB::table('claims')
                ->whereIn('status', ['pending', 'awaiting_signature', 'signed', 'rejected', 'error'])
                ->update(['status' => 'uzklausa']);

            Schema::table('claims', function (Blueprint $table) {
                if (Schema::hasColumn('claims', 'marksign_uuid')) {
                    $table->dropColumn('marksign_uuid');
                }

                if (Schema::hasColumn('claims', 'signing_url')) {
                    $table->dropColumn('signing_url');
                }

                if (Schema::hasColumn('claims', 'signed_pdf_path')) {
                    $table->dropColumn('signed_pdf_path');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('claims')) {
            Schema::table('claims', function (Blueprint $table) {
                if (! Schema::hasColumn('claims', 'marksign_uuid')) {
                    $table->uuid('marksign_uuid')->nullable()->index();
                }

                if (! Schema::hasColumn('claims', 'signing_url')) {
                    $table->text('signing_url')->nullable();
                }

                if (! Schema::hasColumn('claims', 'signed_pdf_path')) {
                    $table->string('signed_pdf_path')->nullable();
                }
            });
        }
    }
};
