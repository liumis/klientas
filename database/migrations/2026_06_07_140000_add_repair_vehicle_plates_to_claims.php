<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claims') && ! Schema::hasColumn('claims', 'repair_vehicle_plates')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->string('repair_vehicle_plates')->default('')->after('last_name');
            });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('scope', 'sharepoint')
                ->where('setting', 'sheet_name')
                ->update(['value' => 'Automatizacija Klientas', 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('claims') && Schema::hasColumn('claims', 'repair_vehicle_plates')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->dropColumn('repair_vehicle_plates');
            });
        }
    }
};
