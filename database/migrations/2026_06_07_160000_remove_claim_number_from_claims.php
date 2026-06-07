<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claims') && Schema::hasColumn('claims', 'claim_number')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->dropColumn('claim_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('claims') && ! Schema::hasColumn('claims', 'claim_number')) {
            Schema::table('claims', function (Blueprint $table) {
                $table->string('claim_number')->default('')->after('email');
            });
        }
    }
};
