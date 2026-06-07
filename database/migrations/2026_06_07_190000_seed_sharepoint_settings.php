<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SharePoint integration settings copied from pakaitinis (secret via env).
     *
     * @return array<int, array{scope: string, setting: string, value: string}>
     */
    private function sharepointSettings(): array
    {
        $rows = [
            ['scope' => 'sharepoint', 'setting' => 'tenant_id', 'value' => 'd0e64f9d-d814-4901-a190-9b013d1707c5'],
            ['scope' => 'sharepoint', 'setting' => 'client_id', 'value' => '445a62d4-3e82-4acf-b68f-5caef4d249e7'],
            ['scope' => 'sharepoint', 'setting' => 'site_name', 'value' => 'https://greenlease.sharepoint.com:/sites/Saugykla'],
            ['scope' => 'sharepoint', 'setting' => 'file_path', 'value' => 'Lithuania/Replacement service/Pakaitiniai servisiniai'],
            ['scope' => 'sharepoint', 'setting' => 'file_name', 'value' => 'Klientu sarasas ir draudimo kontaktai VISI.xlsx'],
            ['scope' => 'sharepoint', 'setting' => 'sheet_name', 'value' => 'Automatizacija'],
            ['scope' => 'sharepoint', 'setting' => 'file_guid', 'value' => '67B74654-CAB8-4F6D-A54B-642BBA3F8AED'],
        ];

        $clientSecret = env('SHAREPOINT_CLIENT_SECRET');

        if (filled($clientSecret)) {
            $rows[] = ['scope' => 'sharepoint', 'setting' => 'client_secret', 'value' => $clientSecret];
        }

        return $rows;
    }

    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $now = now();

        foreach ($this->sharepointSettings() as $row) {
            $match = [
                'scope' => $row['scope'],
                'setting' => $row['setting'],
            ];

            $exists = DB::table('settings')->where($match)->exists();

            if ($exists) {
                DB::table('settings')->where($match)->update([
                    'value' => $row['value'],
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('settings')->insert([
                    ...$match,
                    'value' => $row['value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        $settings = [
            'tenant_id',
            'client_id',
            'client_secret',
            'site_name',
            'file_path',
            'file_name',
            'sheet_name',
            'file_guid',
        ];

        DB::table('settings')
            ->where('scope', 'sharepoint')
            ->whereIn('setting', $settings)
            ->delete();
    }
};
