<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('claims')) {
            return;
        }

        Schema::table('claims', function (Blueprint $table) {
            if (! Schema::hasColumn('claims', 'id_or_passport_number')) {
                $table->string('id_or_passport_number')->default('')->after('license_expires_at');
            }

            if (! Schema::hasColumn('claims', 'id_or_passport_expires_at')) {
                $table->date('id_or_passport_expires_at')->nullable()->after('id_or_passport_number');
            }

            if (! Schema::hasColumn('claims', 'bank_card_number')) {
                $table->string('bank_card_number')->default('')->after('id_or_passport_expires_at');
            }

            if (! Schema::hasColumn('claims', 'bank_card_expires_at')) {
                $table->date('bank_card_expires_at')->nullable()->after('bank_card_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('claims')) {
            return;
        }

        Schema::table('claims', function (Blueprint $table) {
            $columns = [
                'id_or_passport_number',
                'id_or_passport_expires_at',
                'bank_card_number',
                'bank_card_expires_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('claims', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
