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

        $exists = DB::table('settings')
            ->where('scope', 'sharepoint')
            ->whereIn('setting', ['file_guid', 'sourcedoc'])
            ->exists();

        if ($exists) {
            return;
        }

        $hasSharepoint = DB::table('settings')
            ->where('scope', 'sharepoint')
            ->where('setting', 'site_name')
            ->exists();

        if (! $hasSharepoint) {
            return;
        }

        DB::table('settings')->insert([
            'scope' => 'sharepoint',
            'setting' => 'file_guid',
            'value' => '67B74654-CAB8-4F6D-A54B-642BBA3F8AED',
            'created_at' => now(),
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
            ->where('setting', 'file_guid')
            ->where('value', '67B74654-CAB8-4F6D-A54B-642BBA3F8AED')
            ->delete();
    }
};
