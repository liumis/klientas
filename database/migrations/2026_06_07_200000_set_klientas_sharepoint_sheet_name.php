<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('scope', 'sharepoint')
            ->where('setting', 'sheet_name')
            ->update([
                'value' => 'Automatizacija Klientas',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('scope', 'sharepoint')
            ->where('setting', 'sheet_name')
            ->update([
                'value' => 'Automatizacija',
                'updated_at' => now(),
            ]);
    }
};
