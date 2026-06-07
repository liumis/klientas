<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('claims')) {
            return;
        }

        DB::table('claims')
            ->whereNotIn('status', ['uzklausa', 'patvirtinta', 'atsaukta'])
            ->update(['status' => 'uzklausa']);
    }

    public function down(): void
    {
        // Previous status values cannot be restored reliably.
    }
};
