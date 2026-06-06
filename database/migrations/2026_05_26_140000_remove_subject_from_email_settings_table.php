<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('email_settings')) {
            return;
        }

        if (Schema::hasColumn('email_settings', 'subject')) {
            Schema::table('email_settings', function (Blueprint $table) {
                $table->dropColumn('subject');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('email_settings')) {
            return;
        }

        if (! Schema::hasColumn('email_settings', 'subject')) {
            Schema::table('email_settings', function (Blueprint $table) {
                $table->string('subject')->nullable();
            });
        }
    }
};
